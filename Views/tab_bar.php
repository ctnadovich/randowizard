<div class="w3-bar w3-black">
  <button class="w3-bar-item w3-button" onclick="openTab('General-Info')">General-Info</button>
  <button class="w3-bar-item w3-button" onclick="openTab('GPS-Info')">GPS-Info</button>
  <button class="w3-bar-item w3-button" onclick="openTab('Control-Info')">Control-Info</button>
  <button class="w3-bar-item w3-button" onclick="openTab('Cuesheet-Info')">Cuesheet-Info</button>
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
