<?php

namespace engine;

use JetBrains\PhpStorm\ArrayShape;

class SusuApi {

    /**
     * @param string $login
     * @param string $password
     * @return array
     */
	#[ArrayShape(['response' => "\bool|string", 'info' => "mixed", 'error' => "string"])]
    public function login(string $login, string $password): array
    {
		$basicUri = 'https://univeris.susu.ru/student/Account/Login?ReturnUrl=%2fstudent%2fru';
		
		if (is_array($get = Request::get($basicUri)) && stripos($get['response'], 'Object moved') !== false) {
			$get = Request::get($basicUri);
		}
		
		preg_match_all('/\/DXR\.axd\?r=(.*)[$\"]/miD', str_replace('</script>', "</script>\n", $get['response']), $matches);
		preg_match_all('/name=\"__RequestVerificationToken\" type=\"hidden\" value=\"(.*)[$\"]/miD', $get['response'], $matches1);

		$collectForm = [
			'__RequestVerificationToken' => explode('"', $matches1[1][0])[0],
			'UserName' => $login,
			'dxPassword' => $password,
			'LoginBtn' => 'Вход',
			'CurrentLang$State' => '{&quot;validationState&quot;:&quot;&quot;}',
			'CurrentLang_VI' => 'ru',
			'CurrentLang' => 'Русский',
			'CurrentLang$DDDState' => '{&quot;windowsState&quot;:&quot;0:0:-1:0:0:0:-10000:-10000:1:0:0:0&quot;}',
			'CurrentLang$DDD$L$State' => '{&quot;CustomCallback&quot;:&quot;&quot;}',
			'CurrentLang$DDD$L' => 'ru',
			'DXScript' => $matches[1][0],
			'DXCss' => '/student/Content/Css/style.css,/student/Content/favicon.ico,' . $matches[1][2],
			'DXMVCEditorsValues' => '{"UserName":"' . $login . '","dxPassword":"' . $password . '","CurrentLang_DDD_L":["ru"],"CurrentLang":"ru"}',
		];
		
		file_put_contents('ltime.txt', time());
		
		return Request::post('https://univeris.susu.ru/student/ru/Account/Login?ReturnUrl=%2Fstudent%2Fru', $collectForm);
	}
	
	public function request(string $method, string $regexp = '/\'cp\':(.*)\}/m', array $data = []): array|string
    {
	    $str = Request::post('https://univeris.susu.ru/student/ru/' . $method, $data);
	    file_put_contents(str_replace('/', '-', $method) . '.json', json_encode($str, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        preg_match_all($regexp, $str['response'], $matches, PREG_SET_ORDER, 0);
        return str_replace(['"[', ']"', "'[", "]'", "\\\\"], ['[', ']', '[', ']', "\\"], $matches[0][1]);
	}
	
	public function loadLessons(): array
    {
	    $matches = $this->request('StudyPlan/StudyPlanGridPartialCustom', '/\'cpModel\':(.*)}]/m') . '}]';
	    
	    $ret = [];
	    
	    foreach (json_decode($matches, true) as $lesson) {
	        if ($lesson['CycleName'] == 'Б' && $lesson['JournalId'] != '00000000-0000-0000-0000-000000000000') {
	            if (!array_key_exists($lesson['TermNumber'], $ret)) {
	                $ret[$lesson['TermNumber']] = [];
	            }
	            
	            $ret[$lesson['TermNumber']][] = [
	                'desc' => $lesson['DisciplineName'],
	                'name' => $lesson['SubjectName'],
	                'jId' => $lesson['JournalId'],
                ];
	        }
	    }
	    
	    $ret['last_time'] = time();
	    
	    return $ret;
	}
	
	public function loadJournal(string $jId): bool|string
    {
	    $matches = $this->request(
	        'StudyPlan/GetMarks',
            '/\'cpMarkJournal\':(.*)}]/m', ['JournalId' => $jId]
        ) . '}]';

	    $ret = [];
	    
	    foreach (json_decode($matches, true) as $lesson) {
	        $maxPoint = 0;
	        $point = $lesson['Point'];
	        
	        if ($lesson['Rating'] > 0) {
	            $maxPoint = ($point / $lesson['Rating']) * 100;
	        }
	        
	        $ret[] = [
                'rating' => $lesson['Rating'],
                'name' => $lesson['Name'],
                'max' => floor($maxPoint),
                'point' => $point,
                'type' => $lesson['Type'],
            ];
	    }
	    
	    uasort($ret, function($a, $b) {
	        return $b['rating'] - $a['rating'];
	    });
	    
	    return json_encode($ret);
	}
}