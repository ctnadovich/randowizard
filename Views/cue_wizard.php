<div class='w3-card w3-margin w3-padding'>

  <h1>Route Processor</h1>
  <h2>Cue Wizard</h2>

  <div class='italic'>An automated system for processing routes for use by
    the <A HREF=https://github.com/ctnadovich/ebrevet/blob/main/README.md>eBrevet mobile phone app</A>, and for
    making paperwork (eg cue sheets and brevet cards), and much, much more, all
    directly and automatically from route data on Ride With GPS (RWGPS) or other systems.</div>

  <h3>Introduction</h3>

  <IMG src="<?=base_url('assets/local/images/CueWizard.png')?>" WIDTH=25% ALIGN=right>
  <p>The Randonneuring.org web application implements a Route Processor (aka <i>Cue Wizard</i>)
    that allows you to automatically process RWGPS routes
    with minimal
    input and user interaction. The goal of this system is to eliminate the
    burden of generating traditional randonneuring paperwork -- to make the
    RBA and Organizer's life as easy as possible. This Route Processor allows
    randonneuring routes to be created in
    RWGPS or equivalent system, but then the processor takes over -- fetching a private
    copy of the route into the randonneuring.org database
    and publishing various views and data products, including a live eBrevet interface
    and all subsequent paperwork automatically without
    involving RWGPS. Control closings are automatically calculated from the route data
    without needing any special notes.
    With the Cue Wizard Route Processor, the production of brevet cards and cue sheets becomes a
    mechanical, automatic "one-click" process once all data is entered. Should
    something change -- a last minute route adjustment because the pre-ride
    discovers that a bridge is out -- don't panic. One click and Presto!
    All the paperwork and web resources are completely and instantly updated to reflect the change. </p>
  <p>Features of the Cue Wizard Route Processor include:
  <ul>
    <li>Free and available online for any rando club for use.
    <li>Supports <A HREF=https://github.com/ctnadovich/ebrevet/blob/main/README.md>eBrevet mobile phone app</A> with real time rider checkin tracking displayed online.</li>
    <li>One click PDF Cue sheet generation in either Portrait or Landscape
    <li>One click PDF Brevet card generation, 1 or 2 fold cards, inside and outside.
    <li>Automatically generated event web page with links to cue sheets, maps, and routes
    <li>All route data can be entered and maintained in RWGPS or other compatible system.
    <li>Local cache of route data, independent of any third party.
    <li>Automatic RUSA/ACP controle time calculations based on verified algorithms.
      <li>Support for untimed controls.
    <li>Mass start event date/time, or free start
    <li>Worldwide time-zone and "daylight savings time" support
    <li>Download links for GPX, and JSON route files
    <li>Automatic distance segment calculations
    <li>Route maps and elevation profiles
    <li>Automatic search/replace cue note simplification
    <li>Distinctive formatting of Controle cues (staffed, merchant, info...)
    <li>Support for "photo" and "info" controles
    <li>Custom brevet card logo
    <li>Rider roster and finish results support
    <li>Custom club logo and other club specific configuration
    <li>Source code freely available
  </ul>

  <p>The Cue Wizard Route Processor is an independently developed
    system that represents an expansion/enhancement of other systems that
    produce cards and cue sheets, such
    as <A HREF=https://jkassen.org/cards/index.php>Card-O-Matic</a> from New England Randonneurs, and
    <A HREF=https://chrome.google.com/webstore/detail/rando-route-sheet-from-rw/hopalpibikgdnknhjpbejhbiaifjhoaa?utm_source=permalink>Rando
      Route Sheet</a>
    from Seattle International Randonneurs. The main thing that distinguishes
    Cue Wizard from these other systems is that Cue Wizard has a very austere web interface and attempts to provide
    "one click" production of cards and cuesheets
    based on options and attributes associated with the route
    and event that are pre-stored in the route data (eg on RWGPS) as simple <tt>#name=value</tt> tags.


  <p>The Route Processor web application is integrated into the Event Manager at
    the <A HREF=https://randonneuring.org>Randonneuring.Org
      web site</a>.
    Any organizer or RBA at any RUSA club can use this system to support their events through the Randonneuring.org
    website free of charge. For clubs interested in integrating the route processor into their own website, source
    code
    for Cue Wizard and other Randonneuring.org tools are available under the Alfero Open
    Source <A HREF=https://randonneuring.org/LICENSE.txt>License</a>.

  
  <h2>Preparing RWGPS routes for use with Cue Wizard</h2>

  <img src="<?=base_url('assets/local/images/AddToCuesheet')?>" title='Add to Cuesheet' align=right hspace=10px>
  <p>Most of you will use RWGPS to capture route data. Before you can use it effectively with our system,
    you <b>must</b> add these two things to your RWGPS route:
  <ul>
    <li>Cues for Controls &mdash; These cues must be marked as 'control' type. This step is already required by RUSA
      to submit routes to their route library.</li>
    <li>Control Note Attributes &mdash; Cue Wizard requires that each control is fully described with a set of hashtag
      <tt>#name=value</tt> pairs typed in the cue 'notes'. At a
      minimum the <tt>#name</tt>, <tt>#address</tt>, and <tt>#style</tt> of the cue must be specified. For <tt>#style=info</tt> cues, the control
      <tt>#question</tt> must be specified. For <tt>#style=photo</tt>
      controls, the <tt>#photo</tt> must be specified.  If you would like this 
      control to be un-timed per the new liberal ACP rule, add the <tt>#timed=no</tt> attribute.
    </li>
  </ul>
  <p>Once you add those
    things to your route data, your route will process without errors and you can use eBrevet with your event
    and create quality randonneuring cue sheets (landscape or portrait)
    and brevet cards for an event with just one click, as well as many other kinds of paperwork.</p>

  <h4>Adding Notes to Direction Cues</h4>
  <p>
    Once your have your route designed, RWGPS generates servicable cue notes for most ordinary left/right turn
    cues automatically from the map input, but there will always
    be something missing. RWGPS can never
    automatically generate the detailed turn cue notes discriminating randonneurs
    expect in a cue sheet (and conscientious RBAs, Organizers, and Route Owners like
    to provide). Nor can RWGPS automatically generate controle cues. These things
    must be entered manually.</p>
  <p>The RWGPS system has a nice "Add to Cue sheet" mode in the route editor for
    customizing the default cues. As you scan down the auto-generated cues in RWGPS, many can be left unchanged. You
    might be tempted to abbreviate some of them, but you shouln't bother
    as the Cue Wizard system will do quite a bit of automatic search/replace abbreviating, as described below.</p>
  <p>What you should focus on when reviewing cue notes is adding spotting notes to make turns easier to
    spot. Some riders navigate only
    with a cue sheet (yes they still exist). At Randonneuring.org we will usually add things like (SS) for a stop
    sign,
    (TFL) for traffic signal, and so on. We also add an easy to see "Straight" non-turn cue immediately before an
    easy to miss turn. Also, for <em>all</em> rider (even those blindly guided
    by GPS), we suggest adding cue notes warning about road hazards. These warnings will go on the cue
    sheet and will be output by a GPS unit. Finally, consider adding cue notes (and entire cues) identifying
    places to find water, food, and other things tired randonneurs seek. Note: it's best to add all these extra bits
    of info at the end of each Note so
    they don't interfere with the direction text. Leave the direction text at the beginning of the note.</p>
  <p>

  <h4>Adding Controle Cues</h4>
  <p>For each controle, we like to make a total of three cues manually. The cue triplet should be all placed on the
    map very near the controle, but
    RWGPS will force some distance between them. . You can study
    some of our recent RWGPS routes for Randonneuring.org to see how it's done. </p>
  <ol>
    <li> The first cue in the "Controle Triplet" will be a direction cue, usually type Left or Right, sometimes
      Straight,
      and have a Note that says something like "Enter Wawa controle on the left", or
      whatever you want the voice in your phone to say (or GPS display to show). The voice in the phone
      reads the Notes as you get to the controle. No description <tt>#tags</tt> are needed
      on the first cue.</li>
    <li>The middle cue will be the actual controle, cue type
      "Control" (RWGPS spells the Controle "Type" without the final "e"). The middle controle cue should have
      a Note like all cues, but it also needs special <tt>#tags</tt> in the Description.</li>
    <li>Then the
      third cue in the triplet is another ordinary turn cue for departure. Again, this is a direction type, Left or
      Right, with note Something like "Leave Wawa by turning left (same
      direction)". As with the entering cue, description <tt>#tags</tt> are not needed on the final cue of the
      triplet.</li>
  </ol>

  <div class=text-center><img src="<?=base_url('assets/local/images/ControleCues.png')?>" title='Controle Cue Triplet'></div>

  <h4>Start and Finish</h4>
  <p>All intermediate controles should have this "triplet" of cues, but the start and
    finish cues are special cases that only need two cues. There must <em>always</em>
    be at least two control cues defined: a START control in the first km
    of the route, and a FINISH control in the last km of the route. Put a cue with Type
    set to Control in the first or last km and it will be automatically recognized as the START or FINISH. For these
    terminal
    controles, it's OK to just have two cues in the triplet. You can omit the "entrance"
    cue for the START, and the exit cue for the FINISH.
  </p>

  <img src="<?=base_url('assets/local/images/ControleDescription.png')?>" title='Controle Description Tags' align=right hspace=10px>
  <h4>Controle Description <tt>#tags</tt></h4>
  As mentioned above, the middle of the controle cue triplet is the one where the Type
  needs to be set to "Control". For Note on this cue, you can put a note like
  "Welcome to the Blah Blah Control", or whatever you want the app to say or
  GPS to display. But under the Description of the control-type cue, you
  <em>must</em> add <tt>#tags</tt> of the form <tt>#tag=data</tt> to describe the cue. For example,
  the Pottstown controle in our YARRR route has the Description tags
  <div class='padded narrower tt'>
    #name=Wawa<br>
    #address=1520 E High St, Pottstown, PA 19464<br>
    #style=merchant<br>
    #phone=(610) 718-0889<br>
  </div>
  <p>You'll need to paste <tt>#tags</tt> in a description like this for all your cues
    that are set to type Control. If you don't, the system will nag you
    with red errors until you do.</p>

  <p>At a minimum, every control-type cue should have a Description that includes <tt>#name</tt>, <tt>#style</tt>, and
    an <tt>#address</tt>.
    The complete set of <tt>#tags</tt> you can put in a cue Description are the
    following:

  <div class='padded narrower'>
    <tt>#name</tt>=Name of Location.<br>
    <tt>#address</tt>=Full address with street, city, state, zip<br>
    <tt>#style</tt>=Valid styles are: <i>staffed, overnight,
      merchant, open, info, photo, or postcard</i><br>
    <tt>#timed</tt>=no/yes (default is yes)<br>
    <tt>#question</tt>=Question to answer? (Required for info styles)<br>
    <tt>#photo</tt>=What to photograph. (Required for photo styles)<br>
    <tt>#tzname</tt>=Time Zone Name, required only if different than overall TZ<br>
    <tt>#phone</tt>=Phone number of land-line or staff at controle (optional)<br>
    <tt>#comment</tt>=Appended to note (optional)<br>
  </div>

  <p>The <tt>#tags</tt> should be entered as all lowercase, one per line. If setting a value, follow the tag
    with an equal sign and the value. Tags can be listed in any order.
    There's generous forgiveness of whitespace, so you don't have to worry too much about leaving
    (or not leaving) a blank space anywhere. The system will ignore everything
    before the first <tt>#tag</tt>, so if you want to enter stuff in the Description that is not to
    be seen by Cue Wizard, you can do it before the first "hashtag".

  <p>If the control <tt>#style=info</tt> then you must include a <tt>#question</tt> tag defining whot question the
    rider needs
    to answer at the controle. If the controle <tt>#style=photo</tt>, then you must include a <tt>#photo</tt> tag
    defining what
    the rider needs to photograph.

  <p>The <tt>#timed=no</tt> tag will remove the timing requirement for the control, per the new liberal ACP rule. 
  The default is <tt>yes</tt>, which only applies the traditional randonneuring timing to open, overnight, merchant or staffed controls. 
  This tag has no effect on photo, info, or postcard controls -- these are always untimed. 

  <h4>Automatic Search/Replace</h4>
  <p>The automatically generated cue note from RWGPS is a lot more verbose than
    the note on a typical randonneur cue sheet. The RWGPS note will be "Turn right
    onto Main Street" whereas a typically printed rando cuesheet would have only
    the austere "R Main St". On the other hand, when spoken by the RWGPS app using the voice in your
    phone, the verbose text sounds just fine. For this reason, Cue Wizard contains a set of search/replace
    rules for transforming verbose RWGPS cue notes into terse rando cues. That way, the
    voice in the phone can still say the verbose text, while the cue sheet shows
    only a terse cue text. </p>
  <p>You don't have to do anything to your RWGPS data to enable these search/replace rules.
    It will happen to your cue notes automatically. In fact, the less you do to the
    automatically generated notes, the better, as the system works best with the original
    note formats. The printed cues will say "R" not "Right" and verbosity like "Turn onto"
    will be deleted. Review the automatic changes before doing a lot of work simplifying
    cues. You can always make small tweeks to the cue notes manually, and these
    will be generally respected. </p>

  <p>The following is a list of some of the search/replace transformations performed.

  <DIV CLASS='narrower padded ftable'>
    <TABLE WIDTH=100%>
      <TR>
        <TH>Search</TH>
        <TH>Replace</TH>
        <TH>Cue Direction</TH>
      </TR>
      <TR>
        <TD>(Crossing|Cross)</TD>
        <TD></TD>
        <TD>X</TD>
      </TR>
      <TR>
        <TD>Continue (straight onto|onto)</TD>
        <TD>B/C </TD>
        <TD>SO</TD>
      </TR>
      <TR>
        <TD>Turn right onto</TD>
        <TD></TD>
        <TD>R</TD>
      </TR>
      <TR>
        <TD>Turn left onto</TD>
        <TD></TD>
        <TD>L</TD>
      </TR>
      <TR>
        <TD>Turn right (to stay on|to remain on|TRO)</TD>
        <TD>TRO </TD>
        <TD>R</TD>
      </TR>
      <TR>
        <TD>Turn left (to stay on|to remain on|TRO)</TD>
        <TD>TRO </TD>
        <TD>L</TD>
      </TR>
      <TR>
        <TD>T right onto</TD>
        <TD></TD>
        <TD>TR</TD>
      </TR>
      <TR>
        <TD>T left onto</TD>
        <TD></TD>
        <TD>TL</TD>
      </TR>
      <TR>
        <TD>T right (to stay on|to remain on|TRO)</TD>
        <TD>TRO </TD>
        <TD>TR</TD>
      </TR>
      <TR>
        <TD>T left (to stay on|to remain on|TRO)</TD>
        <TD>TRO </TD>
        <TD>TL</TD>
      </TR>
      <TR>
        <TD>(Bear|Slight) right onto</TD>
        <TD></TD>
        <TD>BR</TD>
      </TR>
      <TR>
        <TD>(Bear|Slight) left onto</TD>
        <TD></TD>
        <TD>BL</TD>
      </TR>
      <TR>
        <TD>(Bear|Slight) right TRO</TD>
        <TD>TRO </TD>
        <TD>BR</TD>
      </TR>
      <TR>
        <TD>(Bear|Slight) left TRO</TD>
        <TD>TRO </TD>
        <TD>BL</TD>
      </TR>
      <TR>
        <TD>(Immediate|Immed|Immd) left onto</TD>
        <TD></TD>
        <TD>QL</TD>
      </TR>
      <TR>
        <TD>(Immediate|Immed|Immd) right onto</TD>
        <TD></TD>
        <TD>QR</TD>
      </TR>
      <TR>
        <TD>First left onto</TD>
        <TD></TD>
        <TD>1st L</TD>
      </TR>
      <TR>
        <TD>First right onto</TD>
        <TD></TD>
        <TD>1st R</TD>
      </TR>
      <TR>
        <TD>First left (to stay on|to remain on|TRO)</TD>
        <TD>TRO </TD>
        <TD>1st L</TD>
      </TR>
      <TR>
        <TD>First right onto (to stay on|to remain on|TRO)</TD>
        <TD>TRO </TD>
        <TD>1st R</TD>
      </TR>
      <TR>
        <TD>Second left (to stay on|to remain on|TRO)</TD>
        <TD>TRO </TD>
        <TD>2nd L</TD>
      </TR>
      <TR>
        <TD>Second right onto (to stay on|to remain on|TRO)</TD>
        <TD>TRO </TD>
        <TD>2nd R</TD>
      </TR>
    </TABLE>
  </DIV>


  <h4>Cue Abbreviations</h4>
  <p>We use some common abbreviations in cue notes, and by default a glossary of these
    is printed at the start of a cuesheet. The standard list is:
  <div class='w3-card w3-margin w3-padding' style = 'font-family: "Courier New", monospace;'>
    ***:Easy to miss, B:Bear, B/C:Becomes, FMR:Follow Main Road, L:Left, LMR:Leave Main Road, NM:Not Marked,Q:Quick,
    R:Right, SO:Straight On, SS:Stop Sign, T:T Intersection, TFL:Traffic Light, TRO:To Remain On, X:Cross</div>
  </p>
<!--   <p>If you'd like add, remove, or change some of these cue abbreviations in the glossary that's printed, add
    a <tt>#cue_abbreviations</tt> tag to the description, with a list of abbreviations you want
    to alter. Each abbreviation should have the abbreviation first, then a colon ':', followed by the definition.
    Multiple abbreviations should be separated by
    commas. For example, if you want to add the abbreviation 'WART' for 'Warning Another Railroad Track', and redefine
    'TFL' to now be defined as 'Turn Freekin Left', you
    would add the following tag to the route description in RWGPS.
  <div class='narrower tt'>
    #cue_abbreviations = WART:Warning Another Railroad Track, TFL:Turn Freekin Left
  </div>
  </p>
  <p>All the default abbreviations will still be printed along with your additions. If you want to remove an
    abbrevation, give the abbreviation with a colon
    and don't put anything for the definition. If you don't want any abbreviations printed at all, use
    <tt>#cue_abbreviations=NONE</tt>.
  </p>

  <h4>Comments and In Case of Emergency</h4>
  <p>Above the cue abbreviations, a box is always printed with text in italics inditating what to do in an emergency.
    The
    default text is:
  <div class='narrower text-center'>
    <i>If abandoning ride or to report a problem call the organizer:</i> <tt>{$organizer_name}</tt>
    (<tt>{$organizer_phone}</tt>). <i>For Medical/Safety Emergencies Call 911 First!</i>
  </div>
  </p>
  <p>You
    can change this default text to something else by using the <tt>#in_case_of_emergency</tt> tag.</p>
  <p>Normally, immediately below the Cue Abbreviations the cues will begin, but if you want to print one last commont,
    there is a <tt>#comment</tt> tag that allows you to specify a general text comment that will appear <i>below</i>
    the Cue Abbreviations but
    <i>above</i> the first cue.
  <p> In both of these texts, magic template variables
    such as <tt>{$organizer_name}</tt> and <tt>{$organizer_phone}</tt> are automatically replaced by values for these
    specified for the event.
    There is a long list
    of available variables that can be used in these texts, including most of the description tags.
  </p>
 -->
  <h4>Fetching Route Data</h4>

  <p> After you've added your RWGPS route, and pressed save
    in RWGPS, you must return to Cue Wizard and press the button
    "Fetch latest route from RWGPS". If you don't do that, the Randonneuring.org
    website will not "see" the changes you made in RWGPS. You only
    need to press the "Fetch" button when you've made changes to the route
    in the RWGPS website. Randonneuring.org keeps a cached copy of the RWGPS route data and
    uses this copy for cue and card generation. The copy is refreshed when you
    press this button. </p>

  <div class='w3-card w3-margin w3-padding' style='width: 50%;'><img width=100% src="<?=base_url('assets/local/images/ReFetch.png')?>" title='Re-Downloading Route'></div>

  <h4>Generating Cues and Cards</h4>

  <p>If you entered all your <tt>#tags</tt> correctly, saved in
    RWGPS, and re-downloaded in Cue Wizard, you'll see
    your errors disappear. If you still see errors, fix
    them. Save. Re-download. Eventually you should see no errors and
    the buttons to generate
    cues and cards for the route will appear. </p>


  <div class=text-center><img src="<?=base_url('assets/local/images/CueSheet.png')?>" title='Cue Sheet'></div>

  <p>By default, brevet cards are printed with a blank area where a rider's name
    and address can be written, on attached with a sticker. If the list of riders is available as
    a CSV file, this roster can be uploaded and the card backs will be filled out with the rider names,
    addresses, and a bar code containing the rider name and RUSA number.</p>
  <p>The CSV
    roster file format is similar to that accepted by Card-O-Matic.
    The CSV file <em>must have a header</em> containing column names
    containing strings from this list: RUSA, FIRST, LAST, STREET, ADDRESS, CITY, STATE, and ZIP.
    All columns are optional except either "RUSA" or "LAST". The column names are
    case insensitive. Longer names that contain these strings are accepted (eg FIRST_NAME is
    accepted as FIRST). Column names that don't match the strings listed are ignored.</p>


  <div class=text-center><img src="<?=base_url('assets/local/images/BrevetCard.png')?>" title='Brevet Card'></div>

  <h3>For More Information</h3>

  <p>If you have questions, just ask the Randonneuring.org <A HREF=https://randonneuring.org/info/contact/wizard>wizard</a>.</p>




  <h2>For Developers</h2>

  <p>The software for the Randonneuiring.org website is written in <A HREF=https://www.php.net />PHP</A> and requires
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