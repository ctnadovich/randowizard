<title><?php echo (!empty($title))?$title:'Randonneuring.org'?></title>
<link rel="stylesheet" type="text/css" href='<?php echo base_url("https://randonneuring.org/assets/local/css/master.css")?>'>

<!-- Favicon -->
<link rel="apple-touch-icon" sizes="180x180" href="https://randonneuring.org/assets/local/icon/apple-touch-icon.png">
<link rel="icon" type="image/png" sizes="32x32" href="https://randonneuring.org/assets/local/icon/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="https://randonneuring.org/assets/local/icon/favicon-16x16.png">
<link rel="manifest" href="https://randonneuring.org/assets/local/icon/site.webmanifest">
<link rel="mask-icon" href="https://randonneuring.org/assets/local/icon/safari-pinned-tab.svg" color="#5bbad5">
<meta name="msapplication-TileColor" content="#da532c">
<meta name="theme-color" content="#ffffff">

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