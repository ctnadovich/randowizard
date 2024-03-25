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


<div class='w3-card w3-margin w3-padding'>
    <h1>About Randonneuring.org</h1>

    <p>Randonneuring.org supports a suite of free IT tools that make randonneuring events
        more enjoyable. More fun for participants. Easier on volunteer organizers and RBAs. </p>

    <h2>Manage Events in your Region</h2>
    <div class="w3-panel w3-leftbar w3-light-grey">
        <p class="w3-serif">
            <i>The tools at randonneuring.org support 
                the <A HREF=https://github.com/ctnadovich/ebrevet/blob/main/README.md>eBrevet</a> cell phone application,
                but none of these tools are required in order to use eBrevet. You can manage events with your own 
                system and directly use eBrevet by 
                configuring your website to <A HREF=https://github.com/ctnadovich/ebrevet#clubregion-webserver-support>publish 
                event and route information</a> as required by eBrevet. On the other hand, if you don't want to bother with the 
                web development required to directly support eBrevet, 
                you may find the tools here at randonneuring.org useful.</i>
        </p>
    </div>

    <p>Randonneuring.org provides several randonneuring event management tools. These are intended to
        be used by the RBA or Event Organizer of a region. Once you sign up for
        an account and log in, you will be able to access menu items on the top toolbar that will take you
        to these management tools. They include the following:</p>

    <ul class='fa-ul'>
        <li><i class="fa-li fa fa-user"></i><b>Profile Manager</b> Edit your personal settings as the
            organizer/RBA of the region. This
            includes
            your name, email address, and password.</li>
        <li><i class="fa-li fas fa-map"></i><b>Region Manager</b> Edit settings that describe your region.
            This includes
            your region's name, timezone, website, and other related information.</li>
        <li><i class="fa-li fas fa-biking"></i><b>Event Manager</b> Add events to your region and edit their details. Events you
            add will be listed on the randonneuring.org web site, and will be downloadable
            by the <A HREF=https://github.com/ctnadovich/ebrevet/blob/main/README.md>eBrevet</a> cell phone app. You will also be able to produce paperwork (brevet cards, cue sheets, etc...)
            for
            these events using the Route Processor.</li>
        <li><i class="fa-li fas fa-hat-wizard"></i><b>Route Processor</b> The CueWizard Route processor
            takes a RWGPS route and makes it usable by the eBrevet app. CueWizard will
            also produce quality paperwork for your route to
            distribute at your event, including cue sheets, brevet cards, and more.</li>
    </ul>

    <p>Get up to speed quickly with these tools.
        Read the RBA/Organizer <A HREF=<?= site_url('about/quick_start') ?>>Quick Start Guide</a>.</p>

    <p>Want to know what to tell riders about eBrevet? Send them to the <A HREF=<?= site_url('about/ebrevet_faq') ?>>eBrevet FAQ</A></p>

    <p>Visit the <A HREF='https://github.com/ctnadovich/randowizard/discussions'>randowizard discussion community</A> to hear the latests 
    news about these tools and participate in discussions.

    <p>Find a bug or want to suggest an improvement?
        You can <A HREF='https://github.com/ctnadovich/randowizard/issues'>open
            an issue or suggestion on github</a></p>


    <div class="w3-card w3-center w3-margin w3-padding" style="width:15%; float: right;">
        <A HREF="https://github.com/ctnadovich/ebrevet/blob/main/README.md">
            <img src="https://randonneuring.org/assets/local/images/eBrevet-256.png" style="width: 100%; max-width: 256px;">
            <div class="w3-container w3-center" style='font-size: .7em;'>
                eBrevet
            </div>
        </a>
    </div>

    <h2>Use the eBrevet Electronic Brevet Card</h2>

    <p>The <i>eBrevet</i> Android/iOS app serves as an automated brevet card that can provide
        control check-in verification on a randonneuring brevet or permanent, while maintaining
        some of the "feel" of the traditional paper brevet card process. The app only needs to
        be activated at controls and does not require Internet data service at controls.
        If network access is available, the app will report control check in times for the
        rider to a central server. When the event is completed successfully, the app generates
        a unique Finish Certificate that is sharable on social media.</p>

    <p>You can find the app on both the <A HREF=https://www.apple.com/app-store />Apple Store</a> and the <A HREF=https://play.google.com />Google Play Store</a>.
        Search for "eBrevet" by "CTNadovich".
        For more information read the <A HREF=https://github.com/ctnadovich/ebrevet/blob/main/README.md>eBrevet documentation</a>.
    </P>

    <div class="w3-card w3-center w3-margin w3-padding" style="width:15%; float: right;">
        <A HREF="https://randonneuring.org/about/cue_wizard">
            <img src="https://randonneuring.org/assets/local/images/CueWizard-256.png" style="width:100%; max-width: 256px;">
            <div class="w3-container w3-center" style='font-size: .7em;'>
                CueWizard
            </div>
        </a>
    </div>

    <h2>Cue Wizard Route Processor</h2>

    <p>In order to use your randonneuring route with the event manager and <A HREF=https://github.com/ctnadovich/ebrevet/blob/main/README.md>eBrevet</a> app here at randonneuring.org,
        you need to make your route compatible with our Route Processor called <i>CueWizard</i>. </p>
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

    <p>For more information, read the <A HREF=https://randonneuring.org/about/cue_wizard>documentation of CueWizard</a>. </p>

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