<h3>Your roster was successfully uploaded!</h3>

<ul>
<li>Number of Riders: <?=$n_riders?></li>
<table class='w3-table-all w3-margin'>

<?php
echo "<TR><TH>" . implode('</TH><TH>', array_map(fn($s) => ucfirst(str_replace('_',' ',$s)) , $header)) . "</TH></TR>";
foreach($roster as $r){
    echo "<TR><TD>" . implode('</TD><TD>', $r) . "</TD></TR>";
}
?>
</table>

</ul>