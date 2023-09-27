




<div class="w3-bar w3-black" style="clear: right;">
  <button class="w3-bar-item w3-button" onclick="openTab('General-Info')">Overview</button>
  <button class="w3-bar-item w3-button" onclick="openTab('GPS-Info')">Navigation</button>
  <button class="w3-bar-item w3-button" onclick="openTab('Control-Info')">Controls</button>
</div>

<script>
function openTab(tabName) {
  var i;
  var x = document.getElementsByClassName("tab-container");
  for (i = 0; i < x.length; i++) {
    x[i].style.display = "none";  
  }
  document.getElementById(tabName).style.display = "block";  
}
</script>
