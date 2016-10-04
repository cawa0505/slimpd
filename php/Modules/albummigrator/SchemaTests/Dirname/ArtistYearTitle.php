<?php
namespace Slimpd\Modules\albummigrator\SchemaTests\Dirname;
use Slimpd\RegexHelper as RGX;
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

class ArtistYearTitle extends \Slimpd\Modules\albummigrator\AbstractTests\AbstractTest {
	
	public function __construct($input, &$trackContext, &$albumContext, &$jumbleJudge) {
		parent::__construct($input, $trackContext, $albumContext, $jumbleJudge);
		$this->pattern = "/^" . RGX::NO_MINUS . RGX::GLUE . RGX::MAY_BRACKET . RGX::YEAR . RGX::MAY_BRACKET . RGX::ANYTHING . "$/";
		return $this;
	}
	
	public function run() {
		if(preg_match($this->pattern, $this->input, $matches)) {
			$this->matches = $matches;
			$this->result = 'number-artist-title-ext';
			return;
		}
		$this->result = 0;
	}
	
	public function scoreMatches() {
		cliLog(__CLASS__,10, "purple"); cliLog("  INPUT: " . $this->input, 10);
		if(count($this->matches) === 0) {
			cliLog("  no matches\n ", 10);
			return;
		}
		$this->albumContext->recommend([
			'setArtist' => $this->matches[1],
			'setYear' => az09($this->matches[2]),
			'setTitle' => $this->matches[3]
		]);
		$this->jumbleJudge->albumMigrator->recommendationForAllTracks([
			'setArtist' => $this->matches[1],
			'setYear' => az09($this->matches[2])
		]);
	}
}