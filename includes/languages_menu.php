<?php
/**
 * Created by PhpStorm.
 * User: alexander.c
 * Date: 25/10/2016
 * Time: 16:22
 */
switch ($user_language){
    case 'en':
        $languages_top_navigation ='
            <ul class="navi-level-1">
                <li class="has-sub">
                  <a href="/en">Home</a>
                </li>
                <li class="has-sub">
                  <a href="/en/about-safeink">About Safeink</a>
                </li>
                <li class="has-sub">
                  <a href="/en/what-we-do">What we do?</a>
                </li>
                <li class="has-sub">
                  <a href="/en/how-it-works">How it works?</a>
                </li>
                <li class="has-sub">
                  <a href="en/contact">Contact</a>
                </li>
            </ul>
            ';
    break;
    case 'ru':
        $languages_top_navigation ='
        <ul class="navi-level-1">
            <li class="has-sub">
              <a href="/ru">Главная</a>
            </li>
            <li class="has-sub">
              <a href="/ru/vse-o-safeink">Все о Safeink</a>
            </li>
            <li class="has-sub">
              <a href="/ru/chto-mi-delaem">Что мы делаем?</a>
            </li>
            <li class="has-sub">
              <a href="/ru/kak-eto-rabotaet">Как это работает?</a>
            </li>
            <li class="has-sub">
              <a href="/ru/contacts">Контакты</a>
            </li>
        </ul>
        ';
    break;
}

?>