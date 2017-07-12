<?php

function get_data($page){
    $page_suppliers = 1 + $page;
    $data_get = file_get_contents("https://sp2all.ru/api/getSuppliers/?&page=$page&format=json");
    //$data_get = preg_replace('#^' . chr(0xEF) . chr(0xBB) . chr(0xBF) . '#', '', $data_get);
    return json_decode($data_get);
}

