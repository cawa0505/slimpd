<?php
namespace Slimpd\Models;
/* Copyright (C) 2015-2016 othmar52 <othmar52@users.noreply.github.com>
 *
 * This file is part of sliMpd - a php based mpd web client
 *
 * This program is free software: you can redistribute it and/or modify it
 * under the terms of the GNU Affero General Public License as published by the
 * Free Software Foundation, either version 3 of the License, or (at your
 * option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.  See the GNU Affero General Public License
 * for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
class Discogsitem extends \Slimpd\Models\AbstractModel {
	protected $tstamp;
	protected $type;
	protected $extid;
	protected $response;
	public $trackstrings;
	public $albumAttributes;
	
	public static $tableName = 'discogsapicache';
	
	public function __construct($releaseId = FALSE) {
		if($releaseId === FALSE) {
			return $this;
		}
		if(is_numeric($releaseId) === FALSE || $releaseId < 1) {
			\Slim\Slim::getInstance()->flashNow('error', \Slim\Slim::getInstance()->ll->str('error.discogsid'));
			return;
		}
		$this->setType('release');
		$this->setExtid((int)$releaseId);
		
		$this->fetch();
		
		#var_dump($this);
		#die('nutte');
		$this->extractTracknames();
		$this->extractAlbumAttributes();
		
		return $this;
	}
	
	public function extractTracknames() {
		$data = $this->getResponse(TRUE);
		$counter = 0;
		foreach($data['tracklist'] as $t) {
			$counter++;
			$trackindex = $this->getExtid() . '-' . $counter; 
			$trackstring = $t['position'] . '. ';
			$trackArtists = (isset($t['artists']) === TRUE) ? $t['artists'] : $data['artists'];
			foreach($trackArtists as $a) {
				$trackstring .= $a['name'];
			}
			
			$trackstring .= ' - ' . $t['title'];#
			if(strlen($t['duration']) > 0) {
				$trackstring .= ' [' . $t['duration'] . ']';
			}
			$this->trackstrings[$trackindex] = $trackstring;
		}
	}
	
	public function extractAlbumAttributes() {
		$data = $this->getResponse(TRUE);
		#echo "<pre>" . print_r($data,1); die();
		$this->albumAttributes['artist'] = '';
		foreach($data['artists'] as $a) {
			$this->albumAttributes['artist'] .= $a['name'] . ",";
		}
		
		$data['styles'] = (isset($data['styles']) === TRUE) ? $data['styles'] : array();
		$this->albumAttributes['artist'] = substr($this->albumAttributes['artist'],0,-1);
		$this->albumAttributes['title'] = isset($data['title']) ? $data['title'] : "";
		$this->albumAttributes['genre'] = join(",", array_merge($data['genres'], $data['styles']));
		$this->albumAttributes['year'] = isset($data['released']) ? $data['released'] : "";
		
		// only take the first label/CatNo - no matter how many are provided by discogs
		if(isset($data['labels'][0]) === TRUE) {
			$this->albumAttributes['label'] = $data['labels'][0]['name'];
			$this->albumAttributes['catalogNr'] = $data['labels'][0]['catno'];
		}
		return;
		
		#echo "<pre>" . print_r($data,1); die();
		$counter = 0;
		foreach($data['tracklist'] as $t) {
			$counter++;
			$trackindex = $this->getExtid() . '-' . $counter; 
			$trackstring = $t['position'] . '. ';
			$trackArtists = (isset($t['artists']) === TRUE) ? $t['artists'] : $data['artists'];
			foreach($trackArtists as $a) {
				$trackstring .= $a['name'];
			}
			
			$trackstring .= ' - ' . $t['title'];#
			if(strlen($t['duration']) > 0) {
				$trackstring .= ' [' . $t['duration'] . ']';
			}
			$this->trackstrings[$trackindex] = $trackstring;
		}
	}
	
	public function fetch() {
		if($this->getExtid() < 1 || !$this->getType()) {
			return FALSE;
		}
		
		$item = $this->getInstanceByAttributes(
			['extid' => $this->getExtid(), 'type' => $this->getType()]
		);
		if($item !== NULL) {
			$this->setResponse($item->getResponse());
			return;	
		}
		$app = \Slim\Slim::getInstance();
		$client = \Discogs\ClientFactory::factory([
		    'defaults' => [
		        'headers' => ['User-Agent' => $app->config['discogsapi']['useragent']],
		    ]
		]);
		
		$getter = 'get' . ucfirst($this->getType());
		$response = $client->$getter(['id' => $this->getExtid()]);
		
		$this->setTstamp(time());
		$this->setResponse(serialize($response));
		
		$this->insert();
	}

	public function guessTrackMatch($rawTagDataInstances) {
		$matchScore = array();
		$data = $this->getResponse(TRUE);
		
		foreach($rawTagDataInstances as $rawItem) {
			$localStrings = [
				$rawItem->getArtist(),
				$rawItem->getTitle(),
				$rawItem->getTrackNumber(),
				basename($rawItem->getRelPath())
			];
			$counter = 0;
			foreach($data['tracklist'] as $t) {
				$counter++;
				$extIndex = $this->getExtid() . '-' . $counter;
				
				$extArtistString = '';
				foreach(((isset($t['artists']) === TRUE) ? $t['artists'] : $data['artists']) as $a) {
					$extArtistString .= $a['name'] . ' ';
				}

				if(isset($matchScore[$rawItem->getUid()][$extIndex]) === FALSE) {
					$matchScore[$rawItem->getUid()][$extIndex] = 0;
				}
				$discogsStrings = [
					$extArtistString,
					$t['title'],
					$t['position']
				];
				
				foreach($discogsStrings as $discogsString) {
					foreach($localStrings as $localString) {
						$matchScore[$rawItem->getUid()][$extIndex] += $this->getMatchStringScore($discogsString, $localString);
					}
				}
				
				// in case we have a discogs duration compare durations
				if(strlen($t['duration']) > 0) {
					$extSeconds = timeStringToSeconds($t['duration']);
					$higher = $extSeconds;
					$lower =  $rawItem->getMiliseconds();
					if($rawItem->getMiliseconds() > $extSeconds) {
						$higher = $rawItem->getMiliseconds();
						$lower =  $extSeconds;
					}
					$matchScore[$rawItem->getUid()][$extIndex] += floor($lower/($higher/100));
				}
			}
		}
		$return = array();
		foreach($matchScore as $rawIndex => $scorePairs) {
			arsort($scorePairs);
			$return[$rawIndex] = $scorePairs;
		}
		return $return;
		#echo "<pre>" . print_r($return,1); die();
		#echo "<pre>" . print_r($rawItem,1); die();
		
	}

	private function getMatchStringScore($string1, $string2) {
		if(strtolower(trim($string1)) == strtolower(trim($string2))) {
			return 100;
		}
		return similar_text($string1, $string2);
	}


	//setter
	public function setTstamp($value) {
		$this->tstamp = $value;
		return $this;
	}
	public function setType($value) {
		$this->type = $value;
		return $this;
	}
	public function setExtid($value) {
		$this->extid = $value;
		return $this;
	}
	public function setResponse($value) {
		$this->response = $value;
		return $this;
	}
	
	
	// getter
	public function getTstamp() {
		return $this->tstamp;
	}
	public function getType() {
		return $this->type;
	}
	public function getExtid() {
		return $this->extid;
	}
	public function getResponse($unserialize = FALSE) {
		return ($unserialize === TRUE && is_string($this->response) === TRUE) ? unserialize($this->response) : $this->response;
	}
	
}