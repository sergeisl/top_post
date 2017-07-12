<?php
/**
 * Created by PhpStorm.
 * User: Zver
 * Date: 09.07.2017
 * Time: 14:58
 */
?>
<style>
    .item{
        border-bottom: 1px solid #ccc;
        padding: 5px;
        height: 200px;
    }
    .item:nth-child(even) {
        background: #e7e7fa;
    }
    .addSuppliers{
        margin: auto;
        width: 960px;
        background-color: #f3f3f3;
        padding: 10px;
    }
    .addButtom{
        margin: auto;
        width: 600px;
        text-align: center;
    }
    .eee{
    	background-image:  url('../../img/103.gif');
    	height: 25px;
    	width: 160px;
    	display: inline-flex;
    	display: none;
    }
</style>
<script type="text/javascript" src="../js/jquery.js"></script>
<script src="../js/api.js"></script>
<script>
    var pagen = 1;
</script>
<div>
    <div class="addSuppliers"></div>
    <div class="addButtom">
        <a href="#" class="www" onclick="pagen++;page(pagen);return false;">Ещё 20</a>
        <div class="eee"></div>
    </div>
</div>

