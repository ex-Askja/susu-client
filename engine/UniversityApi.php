<?php

namespace engine;

use Exception;
use JetBrains\PhpStorm\ArrayShape;
use Spatie\Regex\Regex;

class UniversityApi {

    public string $selfUri = 'https://univeris.susu.ru/student/Account/Login';

    const dxScriptMask = '/\/DXR\.axd\?r=(.*)[$\"]/miD';
    const keyMask = '/name=\"__RequestVerificationToken\" type=\"hidden\" value=\"(.*)[$\" \/>]/miD';

    const emptyJid = '00000000-0000-0000-0000-000000000000';
    const qualifierChar = 'Б';

    /**
     * @param string $login
     * @param string $password
     * @return array
     * @throws Exception
     */
	#[ArrayShape(['response' => "\bool|string", 'info' => "mixed", 'error' => "string"])]
    public function login(string $login, string $password): array
    {
        $directory = md5($login) . '/';

		$get = Request::get($this->selfUri, dir: $directory);

        $get['response'] = str_replace('</script>', "</script>\n", $get['response']);

        if ($get['info']['http_code'] === 200 && mb_stripos($get['response'], 'неверный логин') === false) {

            $matches = [
                Regex::matchAll(self::dxScriptMask, $get['response']),
                Regex::matchAll(self::keyMask, $get['response']),
            ];

            $results = [[], []];

            foreach ($results as $k => &$v) {
                if ($matches[$k]->hasMatch()) {
                    foreach ($matches[$k]->results() as $match) {
                        $res = $match->groupOr(1, '');

                        if (
                            (
                                $k === 0
                                && mb_stripos($res, '_') !== false
                                && mb_stripos($res, 'alt') === false
                            ) ||
                            $k > 0
                        ) {
                            $v[] = str_replace(['"', '/>'], ['', ''], $res);
                        }
                    }
                }
            }

            $collectForm = [
                '__RequestVerificationToken' => $results[1][0],
                'UserName' => $login,
                'dxPassword' => $password,
                'LoginBtn' => 'Вход',
                'CurrentLang$State' => '{&quot;validationState&quot;:&quot;&quot;}',
                'CurrentLang_VI' => 'ru',
                'CurrentLang' => 'Русский',
                'CurrentLang$DDDState' => '{&quot;windowsState&quot;:&quot;0:0:-1:0:0:0:-10000:-10000:1:0:0:0&quot;}',
                'CurrentLang$DDD$L$State' => '{&quot;CustomCallback&quot;:&quot;&quot;}',
                'CurrentLang$DDD$L' => 'ru',
                'DXScript' => $results[0][0],
                'DXCss' => '/student/Content/Css/style.css,/student/Content/favicon.ico,' . $results[0][2],
                'DXMVCEditorsValues' => '{"UserName":"' . $login . '","dxPassword":"' . $password . '","CurrentLang_DDD_L":["ru"],"CurrentLang":"ru"}',
            ];

            file_put_contents(Request::defaultDataDir . $directory . 'loadedDataTime.txt', time());

            return Request::post($this->selfUri, $collectForm, dir: $directory);
        }

        return [];
	}

    /**
     * @param string $login
     * @param string $method
     * @param string $regexp
     * @param array $data
     * @return array|string
     * @throws Exception
     */
    public function request(string $login, string $method, string $regexp = '/\'cp\':(.*)\}/m', array $data = []): array|string
    {
        $directory = md5($login) . '/';
	    $str = Request::post('https://univeris.susu.ru/student/ru/' . $method, $data, dir: $directory);
	    file_put_contents(Request::defaultDataDir . $directory . str_replace('/', '-', $method) . '.json', json_encode($str, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        preg_match_all($regexp, $str['response'], $matches, PREG_SET_ORDER, 0);
        return str_replace(['"[', ']"', "'[", "]'", "\\\\"], ['[', ']', '[', ']', "\\"], $matches[0][1]);
	}

    /**
     * @param string $login
     * @return array
     * @throws Exception
     */
    public function loadLessons(string $login): array
    {
	    $matches = $this->request($login, 'StudyPlan/StudyPlanGridPartialCustom', '/\'cpModel\':(.*)}]/m') . '}]';
	    
	    $ret = [];
	    
	    foreach (json_decode($matches, true) as $lesson) {
	        if ($lesson['CycleName'] == self::qualifierChar && $lesson['JournalId'] != self::emptyJid) {
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

    /**
     * @param string $login
     * @param string $jId
     * @return bool|string
     * @throws Exception
     */
    public function loadJournal(string $login, string $jId): bool|string
    {
	    $matches = $this->request(
	        $login,
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