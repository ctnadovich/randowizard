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


<div class=w3-container>
    <h1>About Randonneuring.org</h1>

    <p>Randonneuring.org supports free IT tools that make randonneuring events
        more enjoyable. More fun for participants. Easier on volunteer organizers and RBAs. </p>

    <h2>Tools</h2>

    <h3>Manage Events for your Region</h3>

    <p>The main tool available at randonneuring.org is an event manager for a region. This is intended to
        be used by the RBA or Event Organizer of a region. Once you sign up for
        an account and log in, you will be able to access menu items on the top toolbar that will take you
        to these event management tools. They include the following:</p>

    <ul class='fa-ul'>
        <li><i class="fa-li fa fa-user"></i><b>Profile Manager</b> allows you to edit your personal settings as the
            organizer/RBA of the region. This
            includes
            your name, email address, and password.</li>
        <li><i class="fa-li fas fa-map"></i><b>Region Manager</b> allows you to edit settings that describe your region.
            This includes
            your region's name, timezone, website, and other related information.</li>
        <li><i class="fa-li fas fa-biking"></i><b>Event Manager</b> allows you to add events to your region. Events you
            add will be downloadable
            by the eBrevet cell phone app. You will also be able to produce paperwork (brevet cards, cue sheets, etc...)
            for
            these events.</li>
    </ul>




    <div class="w3-card w3-center w3-margin w3-padding" style="width:15%; float: right;">
        <A  HREF="https://github.com/ctnadovich/ebrevet/blob/main/README.md">
            <img src="https://randonneuring.org/assets/local/images/eBrevet-256.png" style="width: 100%; max-width: 256px;">
            <div class="w3-container w3-center" style='font-size: .7em;'>
                eBrevet
            </div>
        </a>
    </div>

    <h3>Use the eBrevet Electronic Brevet Card</h3>

    <p>The <i>eBrevet</i> Android/iOS app serves as an automated brevet card that can provide
        Electronic Proof of Passage on a randonneuring brevet or permanent, while maintaining
        some of the "feel" of the traditional paper brevet card process. The app only needs to
        be activated at controls and does not require Internet data service at controls.
        If network access is available, the app will report control check in times for the
        rider to a central server. When the event is completed successfully, the app generates
        a unique Proof of Passage Certificate that is sharable on social media.</p>

    <p>You can find the app on both the <A HREF=https://www.apple.com/app-store />Apple Store</a> and the <A HREF=https://play.google.com />Google Play Store</a>.
        Search for "eBrevet" by "CTNadovich".
        For more information read the <A HREF=https://github.com/ctnadovich/ebrevet/blob/main/README.md>eBrevet documentation</a>.
    </P>

    <div class="w3-card w3-center w3-margin w3-padding" style="width:15%; float: right;">
        <A HREF="https://parando.org/cue_wizard.html">
            <img src="https://randonneuring.org/assets/local/images/CueWizard-256.png" style="width:100%; max-width: 256px;">
            <div class="w3-container w3-center" style='font-size: .7em;'>
                CueWizard
            </div>
        </a>
    </div>

    <h3>Create Quality Cue Sheets</h3>

    <p>In order to use your randonneuring route with the event manager and eBrevet app here at randonneuring.org,
        you need to make your route compatible with our Cuesheet processor called <i>CueWizard</i>. </p>
    <p>
        Cue Wizard supports route data entry into the eBrevet app and the production of route paperwork (brevet
        cards and cue sheets). Currently only the mapping tool <A HREF=https://ridewithgps.com>Ride With GPS</a>
        route data format is supported. Other mapping tool
        interfaces are planned. </p>

    <p>Making your route compatible with CueWizard is easy. Minimally, this requires having a custom cue entry
        for each Control checkpoint, and marking that custom cue as "Control" type. This is similar to
        what RUSA requires for their route library. Beyond marking controls in this way, CueWizard supports
        additional markup #tag=value settings that can be placed in the cue Description field. Markup tags include
        support for control questions, address/phone of the control contact, and other options.</p>

    <p>For more information, read the <A HREF=https://parando.org/cue_wizard.html>documentation of CueWizard</a>. </p>

    <h2>Privacy</h2>

    See the <A HREF="https://randonneuring.org/PRIVACY.txt"> Privacy Policy</A>.


    <h2>For Developers</h2>

    <p>The software for the Randonneuiring.org website is written in <A HREF=https://www.php.net />PHP
        </A> and requires
        the <A HREF=https://www.codeigniter.com />CodeIgniter 4</a> framework as well as the <A HREF=http://www.fpdf.org />FPDF</A> library
        <A HREF=https://grocerycrud.com>GroceryCRUD Library</a>, and hosting support
        for a <A HREF=https://www.mysql.com />MySQL</A> database. The source code for this website
        <A HREF=https://github.com/ctnadovich/randowizard>is available for free download</a>
        under the terms of the GNU Affero General Public License.
    </p>

    <div class='w3-panel w3-pale-yellow'>

        <h3>LICENSE</h3>

        <p><em>Copyright (C) 2023 <A HREF=https://nadovich.com/chris/contact.cgi>Chris Nadovich</A></em></p>


        <p>This program is free software: you can redistribute it and/or modify
            it under the terms of the GNU Affero General Public License as published by
            the Free Software Foundation, either version 3 of the License, or
            (at your option) any later version.</p>

        <p>This program is distributed in the hope that it will be useful,
            but WITHOUT ANY WARRANTY; without even the implied warranty of
            MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
            GNU Affero General Public License for more details.</p>

        <p>
        <div class='w3-panel w3-pale-yellow'>
            <div class='w3-center'><A HREF=https://randonneuring.org/LICENSE.txt>DETAILED LICENSE
                    TERMS</A></div>
        </div>
        </p>
    </div>
</div>