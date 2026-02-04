<?php
 
function mi_formato_comentario($comment, $args, $depth) {
    ?>
    <li <?php comment_class('media mb-4'); ?> id="comment-<?php comment_ID(); ?>">
        <div class="media d-flex align-items-start">
            <img class="mr-3 rounded-circle" src="<?php echo esc_url(get_avatar_url($comment, ['size' => 60])); ?>" alt="Avatar">
            <div class="media-body">
                <h5 class="mt-0 mb-1"><?php comment_author(); ?></h5>
                <small class="text-muted"><?php comment_date(); ?> a las <?php comment_time(); ?></small>
                <div class="comment-text mt-2">
                    <?php comment_text(); ?>
                </div>
                <?php
                if ($comment->comment_approved == '0') {
                    echo '<p class="text-warning">Tu comentario está esperando moderación.</p>';
                }
                ?>
                <div class="reply mt-2">
                    <?php comment_reply_link(array_merge($args, array(
                        'reply_text' => 'Responder',
                        'depth' => $depth,
                        'max_depth' => $args['max_depth']
                    ))); ?>
                </div>
            </div>
        </div>
    </li>
    <?php
}