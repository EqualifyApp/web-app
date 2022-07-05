<?php
/**************!!EQUALIFY IS FOR EVERYONE!!***************
 * This document composes the alerts view.
 * 
 * As always, we must remember that every function should 
 * be designed to be as effcient as possible so that 
 * Equalify works for everyone.
**********************************************************/

// Sometimes this view is filtered to a label's data.
if(!empty($_GET['label'])){

    // Load the data of a selected label.
    $filtered_to_label = array(
        array(
            'name' => 'meta_name',
            'value' => $_GET['label']
        )
    );
    $label =  unserialize( DataAccess::get_db_entries(
        'meta', $filtered_to_labels
    ) )[0];

}else{

    // When there's no label data, we set default data.
    $label = array(
        'meta_name' => '', // Default has no id.
        'meta_value' => array(
            array(
                'name' => 'title',
                'value' => 'All Alerts'
            )
        )
    );

}

// Let's extract the "title" meta, so we can use it 
// later and so we can use any label's meta_values to
// fitler the alerts
foreach($label['meta_value'] as $the_meta){
    if( $the_meta['name'] == 'title'){
        $the_title = $the_meta['value'];
        unset($the_meta);
    }
}
print_r($label);

die;

?>

<section>
    <div class="mb-3 pb-3 border-bottom d-flex justify-content-between align-items-center">
        <div>
            <h1><?php echo $the_title;?></h1>
        </div>
        <div>
            <?php
            // If we're not on the main 'All Alerts' page,
            // we are on a label page that can be edited.
            if($the_title !== 'All Alerts'):
            ?>

            <a href="index.php?view=label_customizer&name=<?php echo $label['id'];?>">
                Label Settings
            </a>

            <?php
            endif;
            ?>
        </div>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th scope="col">Time</th>
                <th scope="col">Source</th>
                <th scope="col">URL</th>
                <th scope="col">Type</th>
                <th scope="col">Message</th>
                <th scope="col">Actions</th>
            </tr>
        </thead>

        <?php
        // We need to setup the different filters from the
        // all label meta.
        $filters = $label['meta_value'];
        $alerts = DataAccess::get_db_entries( 'alerts',
            $filters, get_current_page_number()
        );
        if( count($alerts_content) > 0 ): 
            foreach($alerts_content as $alert):    
        ?>

        <tr>
            <td><?php echo $alert->time;?></td>
            <td><?php echo ucwords($alert->source);?></td>
            <td><?php echo $alert->url;?></td>
            <td><?php echo ucwords($alert->type);?></td>
            <td><?php echo covert_code_shortcode($alert->message);?></td>
            <td style="min-width: 200px;">
                <a href="actions/delete_alert.php?id=<?php echo $alert->id;?>" class="btn btn-outline-secondary btn-sm">
                    Dismiss
                </a>
            </td>
        </tr>

        <?php 
        // Fallback
        endforeach; else:
        ?>

        <tr>
            <td colspan="6">No alerts found.</td>
        </tr>

        <?php 
        // End Alerts
        endif;
        ?>

    </table>

    <?php
    // The pagination
    the_pagination($alerts['total_pages']);
    ?>

</section>