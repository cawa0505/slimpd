<?php
namespace Slimpd\Modules\albummigrator;

trait MigratorContext {
	protected $config;
	protected $zeroWhitelist;
	protected $rawTagRecord;
	
	private function configBasedSetters() {
		foreach($this->config as $confSection => $rawTagPaths) {
			if(preg_match("/^" . $this->confKey . "(.*)$/", $confSection, $matches)) {
				$this->runSetters($matches[1], $rawTagPaths);
			}
		}
	}
	
	private function runSetters($setterName, $rawTagPaths) {
		if(method_exists($this, $setterName) === FALSE) {
			cliLog(" invalid config. setter " . $setterName . " does not exists", 10, "red");
			return;
		}
		// TODO: avoid calling unserialize more than one time
		$tagArray = unserialize($this->rawTagRecord['tagData']);
		foreach($rawTagPaths as $rawTagPath) {
			$foundValue = $this->extractTagString(
				recursiveArrayParser(
					trimExplode(".", $rawTagPath),
					$tagArray
				)
			);
			if($foundValue === FALSE || $foundValue === "0") {
				continue;
			}
			$this->$setterName($foundValue);
			$this->recommendations[$setterName][] = $foundValue;
		}
	}

	private function extractTagString($mixed) {
		$out = '';
		if(is_string($mixed))	{ $out = $mixed; }
		if(is_array($mixed))	{ $out = join (", ", $mixed); }
		if(is_bool($mixed))		{ $out = ($mixed === TRUE) ? "1" : "0"; }
		if(is_int($mixed))		{ $out = $mixed; }
		if(is_float($mixed))	{ $out = $mixed; }
		if(trim($out) === '')	{ return FALSE; }
		return trim(strip_tags($out));
	}
	
	public function recommend($properties) {
		foreach($properties as $setterName => $value) {
			$this->recommendations[$setterName][] = remU($value);
		}
	}
	
	public function getMostScored($setterName) {
		#// without recommendations return 
		if(array_key_exists($setterName, $this->recommendations) === FALSE) {
			$getterName = "g" . substr($setterName, 1);
			return $this->$getterName();
		}
		// without recommendations return 
		if(count($this->recommendations[$setterName]) === 1) {
			return $this->recommendations[$setterName][0];
		}
		$mostRelevant = uniqueArrayOrderedByRelevance($this->recommendations[$setterName]);
		return array_shift($mostRelevant);
	}
}
