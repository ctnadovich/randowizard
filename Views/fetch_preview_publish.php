<h3>Event Manager Process</h3>
<P>Whenever you made changes in the route on RWGPS that
    affect the published cues, brevet cards, etc... you must complete the following steps.</p>
<ol>
    <li><b>Fetch</b> the latest data from RWGPS.COM</li>
    <li> <b>Check</b> for errors (fix, and goto step 1) </li>
    <li> <b>Inspect</b> the previews (fix problems, then goto step 1)</li>
    <li> <b>Publish</b> to make it appear live on the event info page</li>
</ol>

<hr width>

<h4>FETCH</h4>

<ul>
    <li><?= $download_note ?></li>
    <li>Last Fetch from RWGPS.COM: <?= $last_download ?></li>
</ul>
<div class="w3-bar-block w3-center" style="width: 50%; margin-left: 10%;">
    <A HREF=<?= $download_url ?> CLASS='w3-bar-item w3-button w3-teal w3-margin'>Fetch latest route from RWGPS</A>
</div>

<h4>PREVIEW</h4>
<P>Press the buttons below for <b>preview versions</b> of the cuesheet and brevet cards generated
    now based on the last route data fetched. <i>If you change the route at RWGPS make sure you re-fetch the
        route data or these previews won't reflect the latest data.</i> Also, this paperwork won't appear live until you press
    the 'Publish Paperwork to Event' button.</P>
<!-- <FORM ACTION=<?= $event_preview_url ?> METHOD=POST enctype="multipart/form-data"> -->

<div class="w3-row">
    <div class="w3-half w3-bar-block w3-center w3-padding">
        <A class="w3-bar-item w3-button w3-margin w3-teal" HREF="<?= $event_preview_url ?>/pdf_cue_portrait">PDF Cuesheet (portrait)</A>
        <A class="w3-bar-item w3-button w3-margin w3-teal" HREF="<?= $event_preview_url ?>/pdf_cue_landscape">PDF Cuesheet (landscape)</A>
        <A class="w3-bar-item w3-button w3-margin w3-teal" HREF="<?= $event_preview_url ?>/csv_cue">CSV Cuesheet</A>
        <A class="w3-bar-item w3-button w3-margin w3-teal" HREF="<?= $event_preview_url ?>/search_replace">Cue Note Rewrite Review</A>
    </div>
    <div class="w3-half w3-bar-block w3-center w3-padding">

    <A class="w3-bar-item w3-button w3-margin w3-teal" HREF="<?= $event_preview_url ?>/card_inside_novalidatefirst">Brevet Card Inside</A>
    <A class="w3-bar-item w3-button w3-margin w3-teal" HREF="<?= $event_preview_url ?>/card_inside">Brevet Card Inside (start stamped)</A>
    <A class="w3-bar-item w3-button w3-margin w3-teal" HREF="<?= $event_preview_url ?>/card_outside_blank">Brevet Card Outside (single blank)</A>
    <A class="w3-bar-item w3-button w3-margin w3-teal" HREF="<?= $event_preview_url ?>/card_outside_roster">Brevet Card Outside (all riders)</A>

</DIV>

</div>
<!-- </FORM> -->

<h4>PUBLISH</h4>
<p>Once you are happy with the preview paperwork, press the button below
    to publish this new version of the paperwork to the event info page. <B>Don't
        forget to publish after you make changes!</b></P>
<ul>
    <li>Cue Version: <?= $cue_version_str ?></li>
    <li>Last Published on: <?= $cue_gentime_str ?></li>
</ul>
<div class="w3-bar-block w3-center" style="width: 50%; margin-left: 10%;">

    <A HREF=<?= $event_publish_url ?> class="w3-bar-item w3-button w3-margin w3-teal">Publish New (Ver <?= $cue_next_version ?>) Cuesheets to Event</A>
</div>