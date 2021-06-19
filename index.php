<?php
require_once 'vendor/autoload.php';

ini_set("display_errors", "On");
ini_set("memory_limit", PHP_INT_MAX . 'MB');
error_reporting(E_ALL);
?>

<html>
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">

        <title>Test</title>

        <link rel="stylesheet" href="assets/semantic.min.css">

        <style>
            .mt-1 {
                margin-top: 1rem;
            }
        </style>
    </head>

    <body>
        <main class="askja-app">
            <div class="ui container mt-1">
                <div class="row">
                    <div class="column">
                        <form class="ui small form">
                            <div class="ui stacked segment">
                                <div class="field">
                                    <div class="ui left icon input">
                                        <i class="user icon"></i>
                                        <input type="text" name="login" id="userName" placeholder="Имя пользователя">
                                    </div>
                                </div>
                                <div class="field">
                                    <div class="ui left icon input">
                                        <i class="lock icon"></i>
                                        <input type="password" name="password" id="userPassword" placeholder="Пароль">
                                    </div>
                                </div>
                                <div class="ui fluid large teal submit button" v-on:click="login">Вход</div>
                            </div>
                            <div class="ui error message"></div>
                        </form>
                    </div>
                </div>
            </div>
        </main>

        <script type="text/javascript" src="https://unpkg.com/vue@3.0.11/dist/vue.global.js"></script>
        <script type="text/javascript" src="assets/jquery.js"></script>
        <script type="text/javascript" src="assets/semantic.min.js"></script>
        <script type="text/javascript" src="assets/app.js?<?= time() ?>"></script>
    </body>
</html>
