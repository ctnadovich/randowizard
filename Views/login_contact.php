
<div id="login-card" class="w3-modal">
    <div class="w3-modal-content w3-card-4">
      <header class="w3-container w3-teal"> 
      <span onclick="document.getElementById('login-card').style.display='none'" 
        class="w3-button w3-display-topright">&times;</span>
        <form action="/login" class="w3-pale-yellow">
        <div class="w3-container w3-padding id="login-card"> 
          <h2>Organizer Log In</h2>
          <p>Log in to use the tools provided by randonneuring.org.</p

          <p><input class="w3-input" type="text" name="first" style="width:90%" required>
          <label>First Name</label></p>

          <p><input class="w3-input" type="text" name="last" style="width:90%" required>
          <label>Last Name</label></p>

          <p><input class="w3-input" type="text" name="rusa_id" style="width:90%" required>
          <label>RUSA ID</label></p>

          <p><input class="w3-input" type="text" name="acp_club_code" style="width:90%" required>
          <label>ACP Club Code</label></p>

          <p><button class="w3-btn w3-black w3-ripple w3-hover-green">Register</button></p>
        </div>
      </form>
    </div>
  </div>

  <div id="contact-card" class="w3-modal">
    <div class="w3-modal-content w3-card-4">
      <header class="w3-container w3-teal"> 
      <span onclick="document.getElementById('contact-card').style.display='none'" 
        class="w3-button w3-display-topright">&times;</span>
        <form action="/contact" class="w3-pale-yellow">
        <div class="w3-container w3-padding id="contact-card"> 
          <h2>Organizer Registration</h2>
          <p>Event organizers and RBAs must register to use the tools provided by randonneuring.org.</p

          <p><input class="w3-input" type="text" name="first" style="width:90%" required>
          <label>First Name</label></p>

          <p><input class="w3-input" type="text" name="last" style="width:90%" required>
          <label>Last Name</label></p>

          <p><input class="w3-input" type="text" name="rusa_id" style="width:90%" required>
          <label>RUSA ID</label></p>

          <p><input class="w3-input" type="text" name="acp_club_code" style="width:90%" required>
          <label>ACP Club Code</label></p>

          <p><button class="w3-btn w3-black w3-ripple w3-hover-green">Register</button></p>
        </div>
      </form>
    </div>
  </div>
