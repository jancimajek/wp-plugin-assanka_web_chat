<?php

class Assanka_WebchatEmoticon {
	private $shortcut = null, $name, $cssclass;
	private $directorylocation = "img/emoticons";

	public static function create($name, $data = array()) {
		$emoticon = new Assanka_WebchatEmoticon;

		$emoticon->setName($name);

		if (!empty($data["shortcut"])) {
			$emoticon->setShortCut($data["shortcut"]);
		}

		if (!empty($data["cssclass"])) {
			$emoticon->setCSSClass($data["cssclass"]);
		}

		return $emoticon;
	}

	public function getURL() {
		$url = Assanka_Webchat::getPluginURL();
		$url .= "/".$this->directorylocation."/".$this->getFileName();

		return $url;
	}

	public function getShortCut() {
		return $this->shortcut;
	}

	public function setShortCut($shortcut) {
		$this->shortcut = $shortcut;
	}

	public function setName($name) {
		$this->name = $name;
	}

	public function getName() {
		return $this->name;
	}

	public function setCSSClass($cssclass) {
		$this->cssclass = $cssclass;
	}

	public function getCSSClass() {
		return "webchat-emoticon-".$this->cssclass;
	}

	public function getPlaceHolder() {
		return "{".$this->name."}";
	}

	public function getFileName() {
		return $this->name.".gif";
	}
}
