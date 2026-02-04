<?php
if (post_password_required()) return;
?>

<div id="comments" class="comments-area mt-5">

  <?php if (have_comments()) : ?>
    <h2 class="comments-title mb-4">
      <?php
      $comments_number = get_comments_number();
      echo $comments_number === 1 ? '1 Comentario' : $comments_number . ' Comentarios';
      ?>
    </h2>

    <ul class="comment-list list-unstyled">
      <?php
      wp_list_comments(array(
        'style' => 'ul',
        'short_ping' => true,
        'avatar_size' => 60,
        'callback' => 'mi_formato_comentario'
      ));
      ?>
    </ul>

    <?php if (get_comment_pages_count() > 1 && get_option('page_comments')) : ?>
      <nav class="comment-navigation my-4" role="navigation">
        <div class="nav-links">
          <div class="nav-previous"><?php previous_comments_link('&larr; Comentarios anteriores'); ?></div>
          <div class="nav-next"><?php next_comments_link('Comentarios siguientes &rarr;'); ?></div>
        </div>
      </nav>
    <?php endif; ?>

  <?php endif; ?>

  <div class="comment-form-wrapper mt-5">
    <?php
    comment_form(array(
      'title_reply' => 'Deja un comentario',
      'class_submit' => 'btn btn-primary mt-3',
      'comment_field' => '<div class="form-group"><label for="comment">Comentario</label><textarea id="comment" name="comment" class="form-control" rows="5" required></textarea></div>',
      'fields' => array(
        'author' => '<div class="form-group"><label for="author">Nombre</label><input id="author" name="author" type="text" class="form-control" required></div>',
        'email'  => '<div class="form-group"><label for="email">Email</label><input id="email" name="email" type="email" class="form-control" required></div>',
      )
    ));
    ?>
  </div>

</div>
