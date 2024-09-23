<p <?php echo get_block_wrapper_attributes(); ?>>
	<?php $verse = "";

	if ($attributes['israndom'])
	{
		$verse = random_bible_verse('0', $attributes['translation'], true);
	}
	else
	{
		$verse = bible_verse_of_the_day('0', $attributes['translation'], true);
	}
	echo str_replace(array("<div class=\"dailyVerses bibleText\">", "</div>"), "", str_replace("</div><div class=\"dailyVerses bibleVerse\">", "<br />", $verse)); ?>
</p>
