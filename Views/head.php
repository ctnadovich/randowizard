<!DOCTYPE html>
<html lang="en">
<!--
//    Randonneuring.org Website Software
//    Copyright (C) 2023 Chris Nadovich
//
//    This program is free software: you can redistribute it and/or modify
//    it under the terms of the GNU Affero General Public License as published by
//    the Free Software Foundation, either version 3 of the License, or
//    (at your option) any later version.
//
//    This program is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU Affero General Public License for more details.
//
//    https://randonneuring.org/LICENSE.txt
//
//    You should have received a copy of the GNU Affero General Public License
//    along with this program.  If not, see <https://www.gnu.org/licenses/>.
-->

<head>
<title><?php echo (!empty($title))?$title:'Randonneuring.org'?></title>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" type="text/css" href='<?php echo base_url("https://randonneuring.org/assets/local/css/master.css")?>'>
<?php 
if(!empty($css_files)){
    foreach($css_files as $file)
        echo "<link type='text/css' rel='stylesheet' href=$file />\n";
}
 
if(!empty($js_files)){
    foreach($js_files as $file)
        echo "<script src=$file></script>\n";
}
?>

<!-- Favicon -->
<link rel="apple-touch-icon" sizes="180x180" href="https://randonneuring.org/assets/local/icon/apple-touch-icon.png">
<link rel="icon" type="image/png" sizes="32x32" href="https://randonneuring.org/assets/local/icon/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="https://randonneuring.org/assets/local/icon/favicon-16x16.png">
<link rel="manifest" href="https://randonneuring.org/assets/local/icon/site.webmanifest">
<link rel="mask-icon" href="https://randonneuring.org/assets/local/icon/safari-pinned-tab.svg" color="#5bbad5">
<meta name="msapplication-TileColor" content="#da532c">
<meta name="theme-color" content="#ffffff">

</head>
<body>
