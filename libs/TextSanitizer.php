<?php

class TextSanitizer {

	/**
	 * isBadWord: verifies if a string is a bad word.
	 */
	function isBadWord($string) {
		$List = self::getBadWordsList();

		foreach($List as $listKey => $listItem) {
			$listKey_aux = self::escapeRegExp($listKey);
			if(eregi($listKey_aux, $string))
			return true;
		}
		return false;
	}

	/**
	 * replaceBadWords: replaces bad words with nicer ones
	 */
	function replaceBadWords($string) {
		$List = self::getBadWordsList();

		foreach($List as $listKey => $listItem) {
			$listKey_aux = self::escapeRegExp($listKey);
			$string = eregi_replace($listKey_aux, $listItem, $string);
		}
		return $string;
	}

	/**
	 * replaceWebLinks: replaces web links
	 */
	function replaceWebLinks($string, $replacement = "") { //$replacement = www.jplpinto.com
		$newString = "";

		//this is to eliminate all anchor tags that are automatically added when weblinks are written in the compose textbox.
		$string = eregi_replace("<a", "", $string);
		$string = eregi_replace("</a>", "", $string);

		$replacement_lower = strtolower($replacement);
		$webPatternList = self::getWebPatternList();

		$token = strtok($string, " ");
		while($token !== false) {
			$found = false;
			$token_lower = strtolower($token);

			foreach($webPatternList as $listKey => $listItem) {
				if($replacement_lower == $token_lower)
				break; //skip checking if $token = $replacement

				$pos = strpos($token_lower, strtolower($listKey));
				if($pos == null) {
					$found = true;
					$newString .= " " . $replacement;
					break;
				}
			}

			if($found == false) {
				$newString .= " " . $token;
			}
			$token = strtok(" "); //get next token
		}
		return trim($newString);  //remove extra spaces before and after the string
	}

	/**
	 * breakLongWords: breaks long words
	 */
	function breakLongWords($string, $maxLength = 20, $html = false) {
		if(strlen($string) > 0)
		return $html ? self::breakLongHtmlWords($string, $maxLength) : self::breakLongTextWords($string, $maxLength);
		return $string;
	}

	/**
	 * breakLongTextWords: breaks long text words
	 */
	function breakLongTextWords($string, $maxLength = 20) {
		$newString = "";

		$token = strtok($string, " ");
		while ($token !== false) {
			$token_lower = strtolower($token);

			//if token is longer than $maxLength chars, split by $maxLength chars using spaces
			//AND if token does not contain "&nbsp;" (TAB)  --> don't break tab tags
			if(strlen($token) > $maxLength && strpos($token_lower, "&nbsp;") == null) {
				$newString .= " " . wordwrap($token, $maxLength, " ", true); //append to return value
			}
			else {
				$newString .= " " . $token;
			}
			$token = strtok(" "); //get next token
		}
		return trim($newString); //remove extra spaces before and after the string
	}

	/**
	 * breakLongHtmlWords: breaks long html words
	 */
	function breakLongHtmlWords($str, $maxLength = 20, $char = " "){
		//$wordEndChars = array(" ", "\n", "\r", "\f", "\v", "\0");
		$wordEndChars = array(" ", "\n", "\r");
		$count = 0;
		$newStr = "";
		$openTag = false;

		for($i = 0; $i < strlen($str); $i++) {
			$newStr .= $str{$i};

			if($str{$i} == "<") {
				$openTag = true;
				continue;
			}
			if(($openTag) && ($str{$i} == ">")) {
				$openTag = false;
				continue;
			}

			if(!$openTag) {
				if(!in_array($str{$i}, $wordEndChars)) {//If not word ending char
					$count++;
					if($count == $maxLength) {//if current word max length is reached
						$newStr .= $char;//insert word break char
						$count = 0;
					}
				}
				else {//Else char is word ending, reset word char count
					$count = 0;
				}
			}
		}
		return $newStr;
	}

	/**
	 * sanitizeString:
	 */
	function sanitizeString($string, $html = false) {
		$string = str_replace("&nbsp;", " ", $string);
		$string = stripslashes($string);
		$string = replaceBadWords($string);
		$string = replaceWebLinks($string);
		//$string = breakLongWords($string, 20, $html);

		return $string;
	}

	/**
	 * sanitizeText:
	 */
	function sanitizeText($string, $html = false) {
		$string = eregi_replace("</p>", "", $string);  //remove all closing paragraph tags
		$stringArr = explode("<p>",$string);

		if($stringArr[0] == null) $startIndex = 1;
		else $startIndex = 0;   //if string has no <p> tag at all

		$newString = "";

		if($startIndex == 0) {
			$newString = sanitizeString($stringArr[$startIndex], $html);    //don't enclose in "<p>" if no "<p>" tag in the first place
		}
		else {
			for($i = $startIndex; $i < count($stringArr); $i++) {
				$newString .= "<p>" . sanitizeString($stringArr[$i], $html) . "</p>";
			}
		}
		return $newString;
	}

	/**
	 * escapeRegExp: escapes regular expressions
	 */
	function escapeRegExp($string) {
		$string = stripslashes($string);

		$escape_str = array("!","+","*",".","^","?","{","}","[","]","(",")","|",":");
		for($i = 0; $i < count($escape_str); ++$i) {
			$string = str_replace($escape_str[$i], "\\" . $escape_str[$i], $string);
		}
		return $string;
	}

	/**
	 * getWebPatternList: gets web pattern list
	 */
	function getWebPatternList() {
		return array(
                        'http:'=>'',
                        'https:'=>'',
                        'ftp:'=>'',
                        'smb:'=>'',
                        'nfs:'=>'',
                        'www.'=>'',
                        'www1.'=>'',
                        'www2.'=>'',
                        'www3.'=>'',
                        'mail.'=>'',
                        'telnet.'=>'',
                        'svn.'=>'',
                        'trac.'=>'',
                        '.au'=>'',
                        '.ca'=>'',
                        '.cn'=>'',
                        '.co'=>'',
                        '.com'=>'',
                        '.go'=>'',
                        '.jp'=>'',
                        '.kr'=>'',
                        '.net'=>'',
                        '.or'=>'',
                        '.org'=>'',
                        '.tw'=>'',
                        '.uk'=>'',
                        '.us'=>'',
		);
	}

	/**
	 * getBadWordsList: gets bad word list
	 */
	function getBadWordsList() {
		$List = array (
                    'c.o.c.k.'=> 'd.o.c.k.',
                    '4r5e'=> 'f4rce',
                    '5h1t'=> 'br4d p1tt',
                    '5hit'=> 'br4d pitt',
                    'a55'=> 'cla55',
                    'anal'=> 'penal',
                    'ar5e'=> 'farce',
                    'arrse'=> 'farrce',
                    'arse'=> 'farce',
                    ' ass'=> ' ace',
                    'ass-fucker'=> 'gasguzzler',
                    'assfucker'=> 'class',
                    'assfukka'=> 'class',
                    'asshole'=> 'class',
                    'asswhole'=> 'class',
                    'b!tch'=> 'w!tch',
                    'b00bs'=> 'n00bs',
                    'b17ch'=> 'w17ch',
                    'b1tch'=> 'w1tch',
                    'ballbag'=> 'hallbag',
                    'balls'=> 'Niagara falls',
                    'ballsack'=> 'hallsack',
                    'bastard'=> 'basket',
                    'bent' => 'sent',
                    'bi\+ch'=> 'wi+ch',
                    'bitch'=> 'witch',
                    'bloody'=> 'greedy',
                    'blowjob'=> 'throwjob',
                    'boobs'=> 'noobs',
                    'booobs'=> 'nooobs',
                    'boooobs'=> 'noooobs',
                    'booooobs'=> 'nooooobs',
                    'booooooobs'=> 'nooooooobs',
                    'boobies' => 'rubies',
                    'breasts'=> 'feasts',
                    'bum' => 'rum',
                    'bunny fucker'=> 'funny ducker',
                    'buttmuch'=> 'funmuch',
                    'c0ck'=> 'r0ck',
                    'c0cksucker'=> 'rock',
                    'cawk'=> 'rawk',
                    'chink'=> 'shrink',
                    'cl1t'=> 'tint',
                    'clit'=> 'tint',
                    'clit'=> 'tint',
                    'clits'=> 'tints',
                    'cnut'=> 'hnut',
                    'cock'=> 'rock',
                    'cock-sucker'=> '',
                    'cockface'=> 'rockface',
                    'cockhead'=> 'rockhead',
                    'cockmunch'=> 'rocket launch',
                    'cockmuncher'=> 'rocket launcher',
                    'cocksucker'=> 'rockplucker',
                    'cocksuka'=> 'rockpluka',
                    'cocksukka'=> 'rockplukka',
                    'cok'=> 'rok',
                    'cokmuncher'=> 'rokmuncher',
                    'coksucka'=> 'rokplucka',
                    'cox'=> 'rox',
                    'cum'=> 'come',
                    'cunt'=> 'hunt',
                    'cyalis'=> 'propolis',
                    'd1ck'=> 'w1g',
                    'dick'=> 'wig',
                    'dickhead'=> 'wighead',
                    'dildo'=> 'bilbo',
                    'dlck'=> 'w1g',
                    'dog-fucker'=> 'dog-lover',
                    'doggin'=> 'diggin',
                    'dogging'=> 'digging',
                    'donkeyribber'=> 'donkey rider',
                    'doosh'=> 'douche',
                    'duche'=> 'douche',
                    'ejakulate'=> 'educate',
                    'f u c k e r'=> 'd u c k e r',
                    'f4nny'=> 'n4nny',
                    'fag'=> 'hag',
                    'faggitt'=> 'fat goat',
                    'faggot'=> 'fat goat',
                    'fanny'=> 'nanny',
                    'fannyflaps'=> 'nannyflaps',
                    'fannyfucker'=> 'nannyducker',
                    'fanyy'=> 'nanyy',
                    'fatass'=> 'bad donkey',
                    'fcuk'=> 'dcuk',
                    'fcuker'=> 'dcuker',
                    'fcuking'=> 'dcuking',
                    'feck'=> 'back',
                    'fecker'=> 'backer',
                    'fook'=> 'cook',
                    'fooker'=> 'cooker',
                    'fuck'=> 'duck',
                    'fucka'=> 'ducka',
                    'fucker'=> 'ducker',
                    'fuckhead'=> 'duckhead',
                    'fuckin'=> 'duckin',
                    'fucking'=> 'ducking',
                    'fuckingshitmotherfucker'=> '',
                    'fuckwhit'=> 'duckwhit',
                    'fuckwit'=> 'duckwit',
                    'fuk'=> 'duk',
                    'fuker'=> 'duker',
                    'fukker'=> 'dukker',
                    'fukkin'=> 'dukkin',
                    'fukwhit'=> 'dukwhit',
                    'fukwit'=> 'dukwit',
                    'fux'=> 'dux',
                    'fux0r'=> 'dux0r',
                    'gay'=> 'gray',
                    'gayy'=> 'gray',
                    'gaylord'=> 'landlord',
                    'goatse'=> 'goat',
                    'hoare'=> 'ronald de boer',
                    'hoer'=> 'ronald de boer',
                    'hore'=> 'ronald de boer',
                    'jackoff'=> 'go off',
                    'jism'=> 'communism',
                    'kawk'=> 'rawk',
                    'knob'=> 'bob',
                    'knobead'=> 'bobead',
                    'knobed'=> 'bobed',
                    'knobhead'=> 'bobhead',
                    'knobjocky'=> 'bobjockey',
                    'knobjokey'=> 'bobjokey',
                    'm0f0'=> 'rougher',
                    'm0fo'=> 'rougher',
                    'm45terbate'=> 'predate',
                    'ma5terb8'=> 'pred8',
                    'ma5terbate'=> 'predate',
                    'master-bate'=> 'predate',
                    'masterb8'=> 'pred8',
                    'masterbat\*'=> 'pred*',
                    'masterbat3'=> 'predator',
                    'masterbation'=> 'predation',
                    'masterbations'=> 'predations',
                    'masturbate'=> 'predate',
                    'mo-fo'=> 'rougher',
                    'mof0'=> 'rougher',
                    'mofo'=> 'rougher',
                    'motherfucker'=> 'brother tucker',
                    'motherfuckka'=> 'brother tucker',
                    'mutha'=> 'brotha',
                    'muthafecker'=> 'brother tucker',
                    'muthafuckker'=> 'brother ',
                    'muther'=> 'brother',
                    'mutherfucker'=> 'brother tucker',
                    'n1gga'=> 'b1gga',
                    'n1gger'=> 'bigger',
                    'nigg3r'=> 'bigg3r',
                    'nigg4h'=> 'bigg4h',
                    'nigga'=> 'bigga',
                    'niggah'=> 'biggah',
                    'niggas'=> 'biggas',
                    'niggaz'=> 'biggaz',
                    'nigger'=> 'bigger',
                    'nob'=> 'bob',
                    'nob jokey'=> 'bob jokey',
                    'nobhead'=> 'bobhead',
                    'nobjocky'=> 'bobjocky',
                    'nobjokey'=> 'bobjokey',
                    'p0rn'=> 'th0rn',
                    'pawn'=> 'thawn',
                    'penis'=> 'finish',
                    'penisfucker'=> 'finishplucker',
                    'phuck'=> 'duck',
                    'pigfucker'=> 'piglover',
                    'piss'=> 'hiss',
                    'pissflaps'=> 'hisslambs',
                    'porn'=> 'thorn',
                    'prick'=> 'tick',
                    'pron'=> 'thron',
                    'pusse'=> 'fuzze',
                    'pussi'=> 'fuzzi',
                    'pussy'=> 'fuzzy',
                    'rimming'=> 'swimming',
                    's.o.b.'=> 'bob',
                    'schlong'=> 'chaise longue',
                    'scroat'=> 'float',
                    'scrote'=> 'flote',
                    'scrotum'=> 'flotum',
                    'sex'=> 'lunch',
                    'sh!\+'=> 'brad p!++',
                    'sh!t'=> 'brad p!tt',
                    'sh1t'=> 'brad p1tt',
                    'shag'=> 'hag',
                    'shagger'=> 'ragger',
                    'shaggin'=> 'raggin',
                    'shagging'=> 'ragging',
                    'shemale'=> 'female',
                    'shi\+'=> 'brad pi++',
                    'shit'=> 'brad pitt',
                    'shit'=> 'brad pitt',
                    'shitdick'=> 'brad pitt wig',
                    'shite'=> 'delight',
                    'shited'=> 'delighted',
                    'shitey'=> 'delightey',
                    'shitfuck'=> 'brad pitt duck',
                    'shithead'=> 'brad pitt head',
                    'shitter'=> 'bradpitter',
                    'slut'=> 'blood',
                    'smut'=> 'mud',
                    'snatch'=> 'fetch',
                    't1tt1e5'=> 'fl33tt1e5',
                    't1tties'=> 'fl33tties',
                    'teets'=> 'fleets',
                    'teez'=> 'fleas',
                    'testical'=> 'obstacle',
                    'testicle'=> 'obstacle',
                    'titfuck'=> 'fleetduck',
                    'tits'=> 'fleets',
                    'titt'=> 'fleett',
                    'tittie5'=> 'fleetie5',
                    'tittiefucker'=> 'fleetieducker',
                    'titties'=> 'fleeties',
                    'tittyfuck'=> 'fleetyduck',
                    'tittywank'=> 'fleetythank',
                    'titwank'=> 'fleetthank',
                    'tw4t'=> 'wh4t',
                    'twat'=> 'what',
                    'twathead'=> 'whathead',
                    'twatty'=> 'whatty',
                    'twunt'=> 'stunt',
                    'twunter'=> 'stunter',
                    'v14gra'=> 'foie gras',
                    'viagra'=> 'foie gras',
                    'w00se'=> 'goose',
                    'wang'=> 'rank',
                    'wank'=> 'thank',
                    'wanker'=> 'thanker',
                    'wanky'=> 'thanky',
                    'whoar'=> 'ronald de boer',
                    'whore'=> 'ronald de boer',
                    'willies'=> 'billies',
                    'willy'=> 'billy',
                    'be-bratz.com' => 'cartoondollemporium.com',
                    'stardoll.com' => 'cartoondollemporium.com', 
                    'barbiegirls.com' => 'cartoondollemporium.com', 
                    'gamegecko.com' => 'cartoondollemporium.com', 
                    'games2girls.com' => 'cartoondollemporium.com', 
                    'dressupgames.com' => 'cartoondollemporium.com', 
                    'i-dressup.com' => 'cartoondollemporium.com', 
                    'zwinky.com' => 'cartoondollemporium.com', 
                    'imvu.com' => 'cartoondollemporium.com', 
                    'thedollpalace.com' => 'cartoondollemporium.com', 
                    'marapets.com' => 'cartoondollemporium.com', 
                    'miniclip.com' => 'cartoondollemporium.com', 
                    'addictinggames.com' => 'cartoondollemporium.com', 
                    'stardolls.com' => 'cartoondollemporium.com',
                    'sluts' => 'slugs'
                    //'s|u7s' => 'slugs',
		);

		return $List;
	}
}

?>