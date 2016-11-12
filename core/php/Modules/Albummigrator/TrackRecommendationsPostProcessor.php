<?php
namespace Slimpd\Modules\Albummigrator;
use \Slimpd\Utilities\RegexHelper as RGX;
/* Copyright (C) 2016 othmar52 <othmar52@users.noreply.github.com>
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

class TrackRecommendationsPostProcessor {

	public static function postProcess($setterName, $value, &$contextItem, $score) {
		if(method_exists(__CLASS__, $setterName) === FALSE) {
			// we dont have a post processer for this property
			return;
		}
		cliLog("    post-processing " . $setterName, 10 , "purple");
		$score = ($score < 0) ? $score*-1 : $score;
		self::$setterName($value, $contextItem, $score);
	}

	public static function setArtist($value, &$contextItem, $score) {
		// "A01. Master of Puppets"
		if(preg_match("/^" . RGX::MAY_BRACKET . RGX::VINYL . RGX::MAY_BRACKET . RGX::GLUE . RGX::ANYTHING . "$/i", $value, $matches)) {
			// TODO: remove this condition as soon as RGX::VINYL is capable for stuff like this
			if(RGX::seemsVinyly($matches[1]) === TRUE) {
				$contextItem->setRecommendationEntry("setTrackNumber", strtoupper($matches[1]), $score*0.1);
				$contextItem->setRecommendationEntry("setArtist", $matches[2], $score*0.1);
				$contextItem->setRecommendationEntry("setArtist", $value, $score*-0.2);
			}
		}
		// "1. Master of Puppets"
		if(preg_match("/^" . RGX::MAY_BRACKET . RGX::NUM . RGX::MAY_BRACKET . RGX::GLUE . RGX::ANYTHING . "$/i", $value, $matches)) {
			$contextItem->setRecommendationEntry("setTrackNumber", removeLeadingZeroes($matches[1]), $score);
			$contextItem->setRecommendationEntry("setArtist", $matches[2], $score);
			$contextItem->setRecommendationEntry("setArtist", $value, $score*-2);
		}
	}

	public static function setTitle($value, &$contextItem, $score) {
		if(preg_match("/^" . RGX::MAY_BRACKET . RGX::VINYL . RGX::MAY_BRACKET . RGX::GLUE . RGX::ANYTHING . "$/i", $value, $matches)) {
			// TODO: remove this condition as soon as RGX::VINYL is capable for stuff like this
			if(RGX::seemsVinyly($matches[1]) === TRUE) {
				$contextItem->setRecommendationEntry("setTrackNumber", strtoupper($matches[1]), $score*0.1);
				$contextItem->setRecommendationEntry("setTitle", $matches[2], $score*0.1);
				$contextItem->setRecommendationEntry("setTitle", $value, $score*-0.2);
			}
		}
		if(preg_match("/^" . RGX::MAY_BRACKET . RGX::NUM . RGX::MAY_BRACKET . RGX::GLUE . RGX::ANYTHING . "$/i", $value, $matches)) {
			$contextItem->setRecommendationEntry("setTrackNumber", removeLeadingZeroes($matches[1]), $score);
			$contextItem->setRecommendationEntry("setTitle", $matches[2], $score);
			$contextItem->setRecommendationEntry("setTitle", $value, $score*-2);
		}
		// "Saban Saulic-Mogu Da Te Kunu (BH-Remix)"
		$chunks = trimExplode("-", $value, TRUE, 2);
		if(count($chunks) === 2) {
			$contextItem->setRecommendationEntry("setArtist", $chunks[0], $score);
			$contextItem->setRecommendationEntry("setTitle", $chunks[1], $score);
			$contextItem->setRecommendationEntry("setTitle", $value, $score*-0.2);
		}
	}

	public static function setTrackNumber($value, &$contextItem, $score) {
		if(isset($value[0]) && $value[0] === "0") {
			$contextItem->setRecommendationEntry("setTrackNumber", removeLeadingZeroes($value), $score);
			$contextItem->setRecommendationEntry("setTrackNumber", $value, $score*-2);
		}
	}

	public static function setYear($value, &$contextItem, $score) {
		$score = (RGX::seemsYeary($value) === TRUE) ? $score : $score*-1;
		$contextItem->setRecommendationEntry("setYear", $value, $score);
	}

	public static function setLabel($value, &$contextItem, $score) {
		// "℗ 2010 Lench Mob Records"
		// "(p) 2009 Lotus Records"
		// "(p) & (c) 2005 Mute Records Ltd"
		// "(P)+(C) 1998 Elektrolux"
		if(preg_match("/^(?:℗|\(p\)|\(p\)[ &+]\(c\))" . RGX::YEAR . "\ " . RGX::ANYTHING ."$/i", $value, $matches)) {
			if(RGX::seemsYeary($matches[1]) === TRUE) {
				$contextItem->setRecommendationEntry("setYear", trim($matches[1]), $score);
				$contextItem->setRecommendationEntry("setLabel", trim($matches[2]), $score);
				$contextItem->setRecommendationEntry("setLabel", $value, $score*-2);
				return;
			}
		}

		// "(c)Subtitles Music (UK)"
		if(preg_match("/^\(c\)" . RGX::ANYTHING ."$/i", $value, $matches)) {
			$contextItem->setRecommendationEntry("setLabel", trim($matches[1]), $score);
			$contextItem->setRecommendationEntry("setLabel", $value, $score*-2);
			return;
		}

		// "a division of Universal Music GmbH"
		if(preg_match("/^a\ division\ of\ " . RGX::ANYTHING ."$/i", $value, $matches)) {
			$contextItem->setRecommendationEntry("setLabel", trim($matches[1]), $score);
			$contextItem->setRecommendationEntry("setLabel", $value, $score*-2);
			return;
		}

		// "Jerona Fruits (JF006)"
		// "World Of Drum & Bass (WODNB003)"
		// "Viper Recordings | VPR051" // TODO: make sure glue gets removed
		// "Jazzman - JMANCD048" // TODO: make sure glue gets removed
		if(preg_match("/^" . RGX::ANYTHING . RGX::GLUE . RGX::CATNR . "$/", $value, $matches)) {
			$contextItem->setRecommendationEntry("setLabel", trim($matches[1]), $score);
			$contextItem->setRecommendationEntry("setCatalogNr", trim($matches[2]), $score);
			$contextItem->setRecommendationEntry("setLabel", $value, $score*-2);
			return;
		}
	}

	/**
	 * in case we have
	 * 	 most scored artist: "Franck Dona & Dan Marciano - Losing My Religion(Chris Kaeser Mix)"
	 *   most scored title : "Losing My Religion(Chris Kaeser Mix)"
	 * remove title from artist
	 *
	 * this test only makes sense AFTER ALL recommentations
	 */
	public static function removeSuffixedTitleFromArtist(&$contextItem) {
		cliLog(__FUNCTION__, 9, "purple");
		$artist = $contextItem->getMostScored("setArtist");
		cliLog("  most scored artist: " . $artist, 10);
		$title = $contextItem->getMostScored("setTitle");
		cliLog("  most scored title : " . $title, 10);
		if(stripos($artist, $title) === FALSE) {
			cliLog("  artist does not contain title", 9);
			return;
		}
		$shortenedArtist = trim(str_ireplace($title, "", $artist), " -");
		cliLog("  shortened artist  : " . $shortenedArtist, 9);
		//$contextItem->recommend(["setArtist" => $shortenedArtist], 5);
		$contextItem->setRecommendationEntry("setArtist", $shortenedArtist, 5);
	}

	/**
	 * in case we have
	 * 	 most scored artist: "Kenny Dope Feat. Screechy Dan"
	 *   most scored title : "Kenny Dope Feat. Screechy Dan - Boomin' In Ya Jeep"
	 * remove artist from title
	 *
	 * this test only makes sense AFTER ALL recommendations
	 * unfortunately this fucks up stuff like "Gusto - Gusto's Groove"
	 */
	public static function removePrefixedArtistFromTitle(&$contextItem) {
		cliLog(__FUNCTION__, 9, "purple");
		if(array_key_exists("setArtist", $contextItem->recommendations) === FALSE) {
			cliLog("  no recommendations for setArtist. skipping...", 10, "darkgray");
			return;
		}
		if(array_key_exists("setTitle", $contextItem->recommendations) === FALSE) {
			cliLog("  no recommendations for setTitle. skipping...", 10, "darkgray");
			return;
		}
		$artistRecommendations = array_keys($contextItem->recommendations['setArtist']);
		$titleRecommendations = array_keys($contextItem->recommendations['setTitle']);

		// avoid doing useless stuff for "albums" with thousands of tracks
		if(count($artistRecommendations) > 50 || count($titleRecommendations) > 50) {
			cliLog("  far too much recommendations. skipping...", 10, "darkgray");
			return;
		}
		foreach($artistRecommendations as $artist) {
			foreach($titleRecommendations as $title) {
				cliLog("  artist: " . $artist, 10);
				cliLog("  title : " . $title, 10);
				if(az09($title) === az09($artist)) {
					cliLog("  pretty much the same. skipping", 10, "darkgray");
					cliLog(" ", 10);
					continue;
				}
				if(stripos(az09($title), az09($artist)) !== 0) {
					cliLog("  artist is not prefixed in title", 10, "darkgray");
					cliLog(" ", 10);
					continue;
				}
				//$contextItem->recommend(["setTitle" => $title], -0.5);
				cliLog("  downvoting title which has prefixed artist", 10);
				$contextItem->setRecommendationEntry("setTitle", $title, -0.5);
			}
		}
	}

	/**
	 * this function checks all artist+title recommendations for beeing "Various Artists", "v.a."
	 * to avoid this situation:
	 * 	 most scored artist: "Various Artists"
	 *   most scored title : "Tenor Saw - Ring The Alarm (Hip Hop Mix)"
	 */
	public static function downVoteVariousArtists(&$contextItem, $setter = "setArtist") {
		cliLog(__FUNCTION__ . " for " . $setter, 9, "purple");
		if(array_key_exists($setter, $contextItem->recommendations) === FALSE) {
			cliLog("  no recommendations for ".$setter.". skipping...", 10, "darkgray");
			if($setter === "setArtist") {
				//recursion. do the same for title recommendations
				return self::downVoteVariousArtists($contextItem, "setTitle");
			}
			return;
		}
		foreach(array_keys($contextItem->recommendations[$setter]) as $itemRecommendation) {
			if(RGX::isVa($itemRecommendation) === FALSE) {
				cliLog("  no need to downvote: " . $itemRecommendation, 10, "darkgray");
				continue;
			}
			cliLog("  found ".$setter." recommendation for downvoting", 9);
			//$contextItem->recommend([$setter => $itemRecommendation], -5);
			$contextItem->setRecommendationEntry($setter, $itemRecommendation, -5);
		}
		if($setter === "setArtist") {
			//recursion. do the same for title recommendations
			return self::downVoteVariousArtists($contextItem, "setTitle");
		}
	}

	/**
	 * this function checks all artist+title recommendations for beeing "Unknown Artists", "Unbekannter Künsteler"
	 * to avoid this situation:
	 * 	 most scored artist: "Unknown Artists"
	 */
	public static function downVoteUnknownArtists(&$contextItem, $setter = "setArtist") {
		cliLog(__FUNCTION__ . " for " . $setter, 9, "purple");
		if(array_key_exists($setter, $contextItem->recommendations) === FALSE) {
			cliLog("  no recommendations for ".$setter.". skipping...", 10, "darkgray");
			if($setter === "setArtist") {
				return self::downVoteUnknownArtists($contextItem, "setTitle");
			}
			return;
		}
		foreach(array_keys($contextItem->recommendations[$setter]) as $itemRecommendation) {
			if(RGX::isUnknownArtist($itemRecommendation) === FALSE) {
				cliLog("  no need to downvote: " . $itemRecommendation, 10, "darkgray");
				continue;
			}
			cliLog("  found ".$setter." recommendation for downvoting", 9);
			//$contextItem->recommend([$setter => $itemRecommendation], -5);
			$contextItem->setRecommendationEntry($setter, $itemRecommendation, -5);
		}
		if($setter === "setArtist") {
			return self::downVoteUnknownArtists($contextItem, "setTitle");
		}
	}

	/**
	 * this function checks all artist recommendations for beeing numeric
	 * to avoid this situation:
	 * 	 most scored artist: "01"
	 */
	public static function downVoteNumericArtists(&$contextItem) {
		cliLog(__FUNCTION__, 9, "purple");
		if(array_key_exists("setArtist", $contextItem->recommendations) === FALSE) {
			cliLog("  no recommendations for setArtist. skipping...", 10, "darkgray");
			return;
		}
		foreach(array_keys($contextItem->recommendations["setArtist"]) as $artistRecommendation) {
			if(is_numeric($artistRecommendation) === FALSE) {
				cliLog("  no need to downvote: " . $artistRecommendation, 10, "darkgray");
				continue;
			}
			cliLog("  found setArtist recommendation for downvoting", 9);
			//$contextItem->recommend(["setArtist" => $artistRecommendation], -5);
			$contextItem->setRecommendationEntry("setArtist", $artistRecommendation, -5);
		}
	}

	/**
	 * this function checks all title recommendations like
	 * "Track 01"
	 * "CD 02 track12"
	 * "CD 1 track 005"
	 * "titel 1"
	 */
	public static function downVoteGenericTrackTitles(&$contextItem) {
		cliLog(__FUNCTION__, 9, "purple");
		if(array_key_exists("setTitle", $contextItem->recommendations) === FALSE) {
			cliLog("  no recommendations for setTitle. skipping...", 10, "darkgray");
			return;
		}
		foreach(array_keys($contextItem->recommendations["setTitle"]) as $titleRecommendation) {
			if(preg_match("/^(cd(?:\d+))?pistaaudio|audiotrack|track|titel(?:\d+)$/", az09($titleRecommendation)) === 0) {
				cliLog("  no need to downvote: " . $titleRecommendation, 10, "darkgray");
				continue;
			}
			cliLog("  found setTitle recommendation for downvoting", 9);
			//$contextItem->recommend(["setTitle" => $titleRecommendation], -2);
			$contextItem->setRecommendationEntry("setTitle", $titleRecommendation, -2);
		}
	}
}
