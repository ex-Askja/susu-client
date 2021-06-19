<?php


namespace engine;


class EduApi {
    public function login(string $login, string $password) {
        $basicUri = 'https://edu.susu.ru/login/index.php';

        if (is_array($get = Request::get($basicUri)) && stripos($get['response'], 'Object moved') !== false) {
            $get = Request::get($basicUri);
        }

        preg_match_all('/type=\"hidden\" name=\"logintoken\" value=\"(.*)[$\" \/\>]/miD', $get['response'], $matches1);

        $collectForm = [
            'logintoken' => explode('"', $matches1[1][0])[0],
            'username' => $login,
            'password' => $password,
            'anchor' => '',
            'rememberusername' => 1
        ];

        file_put_contents('ltime_2.txt', time());

        $post = Request::post('https://edu.susu.ru/login/index.php', $collectForm);

        if (stripos($post['response'], 'sesskey') !== false) {
            preg_match_all('/name=\"sesskey\" value=\"(.*)"/miD', $post['response'], $matches1);
            file_put_contents('sess.txt', $matches1[1][0]);
        }
    }

    public function request(string $method, array|string $data = []) {
        $str = Request::post('https://edu.susu.ru/lib/ajax/service.php?info=' . $method . '&sesskey=' . file_get_contents('sess.txt'), $data, ['content-type' => 'application/json; charset=utf-8']);
        return json_decode($str['response'], true);
    }

    public function loadLessons() {
        // https://edu.susu.ru/lib/ajax/service.php?sesskey=d3WkT2EeFA&info=core_course_get_enrolled_courses_by_timeline_classification
        $matches = $this->request('core_course_get_enrolled_courses_by_timeline_classification', '[{"index":0,"methodname":"core_course_get_enrolled_courses_by_timeline_classification","args":{"offset":0,"limit":0,"classification":"customfield","sort":"fullname","customfieldname":"course_semester","customfieldvalue":"11"}}]');

        if ($matches[0]['error']) {
            return [];
        }

        $ret = [];

        foreach ($matches[0]['data']['courses'] as $c) {
            $ret[$c['id']] = $c['fullname'];
        }

        return $ret;
    }

    // Noty: message_popup_get_popup_notifications + [{"index":0,"methodname":"message_popup_get_popup_notifications","args":{"limit":20,"offset":0,"useridto":"81914"}}]
    // Read all noty: core_message_mark_all_notifications_as_read + [{"index":0,"methodname":"core_message_mark_all_notifications_as_read","args":{"useridto":"81914"}}]


    public function loadJournal(string $jId) {
        $matches = $this->request('StudyPlan/GetMarks', '/\'cpMarkJournal\':(.*)}]/m', ['JournalId' => $jId]) . '}]';

    }
}