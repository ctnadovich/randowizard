<div class='w3-card w3-margin w3-padding'>

    <h1>Quick Start Guide</h1>

    <P>This is a quick start guide for RBAs and Organizers. Refer to the 
        <A HREF='https://randonneuring.org/about'>documentation home page</a> for more information. 
    </P>

    <ol>
        <li><b>REGISTER:</b> Sign up for an account at the <A HREF=https://randonneuring.org>Randonneuing.org website</A>.
            After you log in
            with your new account, make sure all your individual contact info, and detailed info for your region is correct.
            Review these in the
            user profile <i class='fas fa-user'></i> and the region profile <i class='fas fa-map'></i> links.</li>


        <li><b>CREATE EVENT: </b>To create your first event click on the Event Manager <i class='fas fa-biking'></i>.
            Create
            a
            new event. Enter the basic parameters of your brevet like ACP vs RUSA sanction, offical distance, start time
            etc... Most importantly, enter the URL for the RWGPS route. You can come back here to edit your event details
            should they change. Remember,
            if you change the RWGPS link, you'll need to come back here to enter the latest route, then fetch and
            re-validate
            everything.</li>

        <li><b>FETCH ROUTE: </b>After your event is created with all the details set (especially the link URL to the RWGPS
            route!), you should click the
            special Route Processor (Cue Wizard) icon that looks like this: <i class="fas fa-hat-wizard"></i>.
            You'll now be at a page that will allow you to fetch, validate, preview, and publish your route to the event.
            The
            first time you visit
            Cue Wizard for a given route, the Wizard will automatically fetch the route
            data from the RWGPS site and store a local copy with randonneuring.org. If
            you make changes at RWGPS, those changes will not be available to the Route
            Processor till you fetch the data again.
            <i>Don't forget to fetch the updated route.</i> </li>

        <li><B>FIX ERRORS: </B> Cue Wizard only works if
            the route is properly set up in RWGPS. Of course,
            when you first try with your own route you'll likely see some errors. No worries. That's normal. Maybe you
            haven't added controles or control notes <tt>#tags</tt> yet. Read the errors,
            and consult the <A HREF=<?= site_url('about/cue_wizard') ?>>Cue Wizard documentation</a>. They'll help guide you toward what you
            need
            to add
            to your RWGPS route in order to make it work. Don't forget to fetch again after making RWGPS changes.</li>
        <li><b>PREVIEW: </b>Once you have fixed all the errors and fetched the latest RWGPS data,
            you can preview the paperwork for the event (cue sheets, brevet cards, etc...). Very often
            you will notice more errors when you look over the paperwork. No worries. That's normal. Fix them and fetch
            again.
        </li>
        <li><b>PUBLISH: </b>Once you are SURE you have fixed all the errors it's time to publish the route. Once
            published,
            the event and route will be
            automatically available to <A HREF=https://github.com/ctnadovich/ebrevet/blob/main/README.md>eBrevet</a> and all the event details will appear live on the randonneuring.org website.
        </li>
        <li><b>LINK: </b>With your event published at randonneuring.org, if you so desire
            you can put links on your club/regional
            web page that direct people to the event info and roster info pages at 
            randonneuring.org. The URLs to link are
            <ul>
            <li><b>Past and Future Events for your region: </b> https://randonneuring.org/regional_events/<code>&LT;ACP CLUB CODE&GT;</code></li>
                <li><b>Future Events JSON format: </b> https://randonneuring.org/ebrevet/future_events/<code>&LT;ACP CLUB CODE&GT;</code></li>
                <li><b><i class="fa-solid fa-circle-info"></i> Info about a specific event: </b> https://randonneuring.org/event_info/<code>&LT;EVENT CODE&GT;</code></li>
                <li><b><i class="fa-solid fa-users"></i> Rider roster, results, and check in status: </b> https://randonneuring.org/roster_info/<code>&LT;EVENT CODE&GT;</code>
                </li>
            </ul>
            where the <code>&LT;EVENT CODE&GT;</code> is the unique identifying code for the event that combines 
            your ACP Club Code with an event ID number (eg 905106-123). These event codes
            can be seen listed in the event manager and elsewhere on randonneuring.org. 
        <li><B>CONGRATULATIONS: </B>You've published your event and route data with randonneuring.org.    </ol>

</div>