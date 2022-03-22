<section>
    <h1 class="mb-3 pb-4 border-bottom">All Events</h1>
    <table class="table">
        <thead>
            <tr>
                <th scope="col">Type</th>
                <th scope="col">Time</th>
                <th scope="col">Property</th>
                <th scope="col">Status</th>
            </tr>
        </thead>

        <?php
        // Begin Events
        $events = get_events($db);
        if(count($events) > 0 ): foreach($events as $event):    
        ?>

        <tr>
            <td><?php echo ucwords(str_replace('_', ' ', $event->type));?></td>
            <td><?php echo $event->time;?></td>
            <td>
                <a href="?view=property_details&id=<?php echo $event->property_id;?>">
                    <?php echo get_property_title($db, $event->property_id);?>
                </a>
            </td>
            <td><?php echo ucwords($event->status);?></td>
        </tr>

        <?php 
        // Fallback
        endforeach; else:
        ?>

        <tr>
            <td colspan="4">No events found.</td>
        </tr>

        <?php 
        // End Events
        endif;
        ?>

    </table>
</section>