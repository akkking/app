<div class="blog-comment">
<?php
	if( ! $comment[ "hidden" ] ):
?>
	<a name="<?php echo isset( $comment["anchor"][2] ) ? $comment["anchor"][2] : "" ?>"></a>
	<div class="comment_avatar">
		<?php echo $comment["avatar"] ?>
	</div>
	<div class="comment">
		<div class="details">
			<strong><?php echo $comment["sig"] ?></strong>
			<span class="timestamp"><?php echo $comment["timestamp"] ?></span>
		</div>
		<?php
			echo $comment["text"];
		?>
	</div>
<?php
	else:
		echo wfMsg( "blogs-comment-hidden" );
		echo '&nbsp;<a href="#" class="blog-comm-hide" id="'.$comment[ "title" ]->getArticleId().'">unhide</a>';
	endif; ### comment[ "hiddent" ]
?>
</div>
<br style="clear: both;" />
