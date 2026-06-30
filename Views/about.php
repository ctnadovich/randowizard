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
         <li><i class="fa-li fa fa-user"></i><b>Organizer Manager</b> Set up access for the
            organizer/RBAs of the region.</li>
        <li><i class="fa-li fas fa-map"></i><b>Region Manager</b> Edit settings that describe your region and its events.
            This includes
            your region's name, timezone, website, and other related information.</li>
        <li><i class="fa-li fas fa-biking"></i><b>Event Manager</b> Add events to your region and edit their details. Events you
            add will be listed on the randonneuring.org web site, and will be downloadable
            by the <A HREF=https://github.com/ctnadovich/ebrevet/blob/main/README.md>eBrevet</a> cell phone app. You will also be able to produce paperwork (brevet cards, cue sheets, etc...)
            for
            these events using the Route Processor.</li>
    </ul>

    <p>Get up to speed quickly with these tools.
        Read the RBA/Organizer <A HREF=<?= site_url('about/quick_start') ?>>Quick Start Guide</a>.</p>

    <p>Want to know what to tell riders about eBrevet? Send them to the <A HREF=<?= site_url('about/ebrevet_faq') ?>>eBrevet FAQ</A></p>

    <p>Find a bug or want to suggest an improvement?
        You can <A HREF='https://github.com/ctnadovich/randowizard/issues'>open
            an issue or suggestion on github</a></p>




    <h2>Organizer Profile Manager <i class='fa fa-user'></i></h2>

    <p>After the first person "claims" a randonneuring region, that person can use their access to add additional RBAs/Organizers who can manage the region. This is done simply under the Profile <i class='fa fa-user'></i> link on the top menu bar or hamburger menu (My Profile). 
    Under that link you'll see a table listing all the current organizers that allows cloning/editing/deleting of each profile. There is also a "Add RBA/Organizer" button that allows you to add new organizers to a given region. </p>

    <h2>Region Manager <i class='fas fa-map'></i></h2>

    <p>Details of a region, name, club name, logo, mailing address, etc.. is managed under the Region link <i class="fas fa-map"></i> in the top menu bar, or My Region in the hamburger menu. 

    <h2>Event Manager <i class='fas fa-biking'></i></h2>



    <p>The Event Manager is the main work area of randonneuring.org. To begin managing your events, click on the
        events icon <i class="fas fa-biking"></i> on the upper right, or choose "My Events" in the hamburger menu.
    </p>
       <IMG SRC=https://randonneuring.org/assets/local/images/EventProcessorMenuLinks.png style="width:100%; max-width: 800px;">


   

    <p>Creating an event is easy -- you click the PLUS sign next to "Add Future Event" and basically just fill out the form.
        Enter the basic parameters of your brevet like the name of the event (without the distance), the distance (separately), ACP vs RUSA sanction, offical distance, start time
        etc... Most importantly, enter the URL for the RWGPS route. You can come back here to edit your event details
        should they change. Remember,
        if you change the RWGPS link, you'll need to come back here to enter the latest route, then fetch and
        re-validate
        everything.

        <div class="w3-panel w3-leftbar w3-amber">
        <p class="w3-serif">
            <i>When you create an event, the name field should <b>not</b> include the distance.  
                Please specify the distance only in the distance field. If you put the distance 
                in the name, because distance is typically printed after the name, you'll see the distance 
                printed twice in a lot of places. You'll see 
                things like "Our Most Excellent 200K 200K". You don't want that. 
                Please don't include the distance in the name of the event. Thank you for your cooperation. 
            </i>
        </p>
    </div>

 
    <p>Once you've entered the basics for the event and saved out of the event editor, the buttons in the event table offer several things you can do with your event. </p>

    <ul class='fa-ul'>
           <li><i class="fa-li fas fa-hat-wizard"></i><b>Route Processor</b> The CueWizard Route processor
            takes a RWGPS route and makes it usable by the eBrevet app. CueWizard will
            also produce quality paperwork for your route to
            distribute at your event, including cue sheets, brevet cards, and more.</li>
        <li><i class="fa-li fa fa-users"></i><b>Roster Manager</b> Manage rider registration for your
            event, and produce necessary paperwork (waivers, cuesheets, brevet cards, postcards, ... in a variety of formats.)</li>
       <li><i class="fa-li fa fa-download"></i><b>Generate Outputs</b> One benefit of entering your events and routes into randonneuring.org is the ability
    to automatically generate a myriad of outputs, reports, and other mashups of the event/route data. These include quality cue sheets, official brevet cards, signup sheets, CSV downloads, and support for eBrevet.</li>
<li><i class='fa-li fa fa-share-alt'></i><b>Share Pages</b> Another benefit of entering your events and routes into randonneuring.org are the automatically
generated web pages and JSON objects that can be incorporated, shared, or linked at your club website.</li>
    </ul>
        
             <IMG SRC=https://randonneuring.org/assets/local/images/EventOutputButtonsLabeled.png style="width:100%; max-width: 800px;">

    
     <p>Usually the first thing you'll want to do after entering an event (with route) is to 
        "publish" the route and paperwork. This includes publication of the cue sheet, brevet cards, and the control information for use by eBrevet. Publishing is accomplished by the Cue Wizard page, described below.
        After your event is created with all the details set (especially the link URL to the mapped
        route!), you should click the
        special Route Processor (Cue Wizard) icon that looks like this: <i class="fas fa-hat-wizard"></i>.
        You'll now be at a page that will allow you to fetch, validate, preview, and publish your route to the event. 
        
        
                Once
            published,
            all the data for the event and route is ready to be made available on the randonneuring.org website and in eBrevet.
            <i>NB: New events are hidden by default. You'll need to go to the event manager <i class='fas fa-biking'></i> and
                remove "Hidden" from the status for the published event to be fully visible. The event isn't visible to the public until it's "un-hidden" and published. These are two separate steps.
        By default new events are hidden. That way people don't see your event till you are ready for them to see it. Usually you want to get all the details settled, cross the tees, dot the i's.</i>
</p>

         

            <h2>Cue Wizard Route Processor <i class="fas fa-hat-wizard"></i></h2>

            <div class="w3-card w3-center w3-margin w3-padding" style="width:15%; float: right;">
                <A HREF="https://randonneuring.org/about/cue_wizard">
                    <img src="https://randonneuring.org/assets/local/images/CueWizard-256.png" style="width:100%; max-width: 256px;">
                    <div class="w3-container w3-center" style='font-size: .7em;'>
                        CueWizard
                    </div>
                </a>
            </div>

<p>In order to use your randonneuring route with the tools here at randonneuring.org,
                you need to make your route compatible with our <A HREF=https://randonneuring.org/about/cue_wizard>Cue Wizard Route Processor</a>.  
                Cue Wizard supports route data entry into the eBrevet app and the production of route paperwork (brevet
                cards and cue sheets). Currently only the mapping tool <A HREF=https://ridewithgps.com>Ride With GPS</a>
                route data format is supported. Other mapping tool
                interfaces are planned. </p>

<p>Making your route compatible with CueWizard is easy. Minimally, this requires having a custom cue entry
                for each Control checkpoint, and marking that custom cue as "Control" type. This is similar to
                what RUSA requires for their route library. Beyond marking controls in this way, CueWizard requires
                additional markup #tag=value settings that can be placed in the cue Description field. Markup tags include
                support for control questions, address/phone of the control contact, and other options.  The cue "Notes" can be anything, but 
            Cue Wizard uses special search/replace abbreviations to simplify these notes when printed on cue sheets.</p>

                <p>Without these required additions, the route can't be published at randonneuring.org. Cue Wizard only works if
            the route is properly set up in RWGPS. If you choose a route that's not yet been set up,  you'll likely see some errors and you won't be able to generate or publish anything. 
            No worries. That's normal. Just mark controles and add control cue description <tt>#tags</tt> yet. Read the errors,
            and consult the <A HREF=<?= site_url('about/cue_wizard') ?>>Cue Wizard documentation</a>. They'll help guide you toward what you
            need
            to add
            to your RWGPS route in order to make it work. Don't forget to fetch again after making RWGPS changes.
</p>

<p>The first time you visit
        Cue Wizard for a given route, the Wizard will automatically fetch the route
        data from the RWGPS site and store a local copy with randonneuring.org. If
        you make changes at RWGPS, those changes will not be available to the Route
        Processor till you fetch the data again.
        <i>Don't forget to fetch an updated route after changing it in RWGPS.</i>
</p>

        <p>Once you have fixed all the errors and fetched the latest RWGPS data,
            you can preview the paperwork for the event (cue sheets, brevet cards, etc...).  Very often
            you will notice more errors when you look over the paperwork. No worries. That's normal. Fix them and fetch
            again. Then, once you are SURE you have fixed all the errors, it's time to publish the route. 



            <p>For more information, read the <A HREF=https://randonneuring.org/about/cue_wizard>documentation of CueWizard</a>. </p>

                            <h2>Roster Manager <i class="fa fa-users"></i></h2>

    <p>With the roster of your event in the event manager, many "per-rider" functions and outputs become possible. Not only does the 
        roster contain the riders name and ID information, the roster also holds the official rider results for the event (eg: finish time).
Automatically saved rider checkins and finish results uploaded from eBrevet require a roster in the event manager. And, of course, official results upload by CSV file require the roster with results. </p>

        <p>The Riders button in the event table allows you to manage the rider roster for your event.</p>

                 <IMG SRC=https://randonneuring.org/assets/local/images/ManageRidersMenu.png style="width:100%; max-width: 175px;">

<p>There are two ways to enter a roster into your event. You can add riders individually through the "Manage Roster" table, 
or you can upload a CSV file that contains your complete roster.</p>
        <p>The manual roster manager allows you to add/edit/remove riders individually. Riders added must be RUSA members in order for them to be entered manually. The system 
will autocomplete the rider name as you enter it using the RUSA membership data (updated once per day by RUSA). </p>

<p>The CSV file upload expects a format 
 compatible with Card-O-Matic and other systems. The CSV file must have a first-row header giving column names. 
 Column RIDERID is required. Coulumn LAST is required if your region has membership vetting (eg Randonneurs USA / RUSA). 
 Valid Columns (may be in any order) are:</p>

 <ul>
 <li>"RIDERID" or variations ("rider id", "rusa", "rusa number", "member", "acp", etc)
 <li>"FIRST" or variations ("firstname", "first name", etc)
 <li>"LAST" or variations
 <li>"ADDRESS" or "STREET" (multiples combined, eg address1, address2...)
 <li>"CITY"
 <li>"STATE"
 <li>"ZIP"
 </ul>

 <p>Uploading a roster erases any previous roster, however if a rider has a FINISH time result, that result will be preserved -- useful if there were
    preriders before the final roster is uploaded.</p> 




                            <h2>Generate Outputs <i class="fa fa-download"></i></h2>

    <p>The "Generate" functions save time and effort for RBAs and organizers. Here you will find functions that automatically generate the various 
        paperwork and data objects that are needed by randonneuring events. With little more than a click of the mouse, you can produce</p>

        <ul>
            <li>Sign In Sheets</li>
            <li>Cue Sheets</li>
            <li>Brevet Cards</li>
            <li>Waivers</li>
            <li>Postcards</li>
            <li>Results File (CSV)</li>
        </ul>

    <p>These outputs are generated using the roster information, so you can get all the 
        brevet cards, waivers, postcards, and roster sheets 
        automatically pre-printed with the rider names. Cue sheets can be landscape or portrait, and use traditional abbreviations and other formatting 
    enhancements making them far superior to the basic cuesheet you get from Google or RWGPS route maps. With the roster updated with rider finish times, 
an official results CSV file is available for upload. </p>


    <h2>Sharable Pages and JSON for your Club Website <i class='fa fa-share-alt'></i></h2>

        <p>With your event published at randonneuring.org, if you so desire
            you can put links on your club/regional
            web page that direct people to the event info and roster info pages at
            randonneuring.org. Some of these links are visible under the Info button. You can link to these pages or include them in your site. CSS markup can be adjusted through the Region Manager, so you can make these pages look more like your club website. There are 
            also JSON objects that reflect all the public data available through randonneuring.org. The JSON can be used to generate your own views and mashups of the data.</p>

            <p>The direct URLs to link include</p>
            <ul>
                <li><b>Past and Future Events for your region: </b> https://randonneuring.org/regional_events/<code>&LT;ACP CLUB CODE&GT;</code></li>
                <li><b>Future Events JSON format: </b> https://randonneuring.org/ebrevet/future_events/<code>&LT;ACP CLUB CODE&GT;</code></li>
                <li><b><i class="fa-solid fa-circle-info"></i> All info about a specific event: </b> https://randonneuring.org/event_info/<code>&LT;EVENT CODE&GT;</code></li>
                <li><b><i class="fa-solid fa-circle-info"></i> All info about a specific event (JSON): </b> https://randonneuring.org/event_info/<code>&LT;EVENT CODE&GT;</code>/json</li>
                <li><b><i class="fa-solid fa-users"></i> Rider roster and results: </b> https://randonneuring.org/roster_info/<code>&LT;EVENT CODE&GT;</code>
                <li><b><i class="fa-solid fa-list-check"></i> Control check ins: </b> https://randonneuring.org/checkin_status/<code>&LT;EVENT CODE&GT;</code>
                <li><b><i class="fa-solid fa-list-check"></i> Control check ins (JSON): </b> https://randonneuring.org/checkin_status/<code>&LT;EVENT CODE&GT;</code>/json</li>
        </ul>
        <p>where the <code>&LT;EVENT CODE&GT;</code> is the unique identifying code for the event that combines
        your ACP Club Code with an event ID number (eg 905106-123). These event codes
        can be seen listed in the event manager and elsewhere on randonneuring.org.
        If you don't like the style of these pages, you can use the advanced 'style_html', 'header_html', and 'footer_html' fields
        in the region settings to tune the styling of the pages to match your home website. Or, alternatively, fetch
        the <code>event_info/&LT;EVENT CODE&GT;/json</code> for your event and
        construct your page from this data. The complete JSON info includes event info, roster, control details, and checkins all in one JSON object.
                </p>

       
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

                <p><em>Copyright (C) 2026 <A HREF=https://nadovich.com/chris/contact.cgi>Chris Nadovich</A></em></p>


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