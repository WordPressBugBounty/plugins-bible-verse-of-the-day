<?php
/**
 * Plugin Name: Bible Verse of the Day
 * Description:       The daily Bible verse or a random Bible verse on your website, from DailyVerses.net
 * Requires at least: 5.3
 * Requires PHP:      7.0
 * Version:           2.7
 * Author:            DailyVerses.net
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       bible-verse-of-the-day
 *
 * @package           create-block
 */

/**
 * Registers the block using the metadata loaded from the `block.json` file.
 * Behind the scenes, it registers also all assets so they can be enqueued
 * through the block editor in the corresponding context.
 *
 * @see https://developer.wordpress.org/reference/functions/register_block_type/
 */
function create_block_bible_verse_of_the_day_block_init() {
	register_block_type( __DIR__ . '/build' );
}
add_action( 'init', 'create_block_bible_verse_of_the_day_block_init' );

function bible_verse_of_the_day_load_plugin_textdomain() {
    load_plugin_textdomain( 'bible-verse-of-the-day', FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
}

add_action( 'plugins_loaded', 'bible_verse_of_the_day_load_plugin_textdomain' );

function bible_verse_of_the_day($showlink, $language='niv', $isblock=false) 
{	
	if (!$isblock)
	{
		wp_register_style( 'prefix-style', plugins_url('bible-verse-of-the-day.css', __FILE__) );
		wp_enqueue_style( 'prefix-style' );
	}

	if ($language == '')
	{
		$language == 'niv';
	}
	
	$languageAdd = get_language_add($language);
	$languageUrl = get_language_url($language);
	
	$bibleVerseOfTheDay_Date = get_option('bibleVerseOfTheDay_Date' . $languageAdd);
	$bibleVerseOfTheDay_bibleVerse = get_option('bibleVerseOfTheDay_Verse' . $languageAdd);
	$bibleVerseOfTheDay_lastAttempt = get_option('bibleVerseOfTheDay_LastAttempt' . $languageAdd);
				
	$bibleVerseOfTheDay_currentDate = date('Y-m-d');
	
	if (!is_valid_verse($bibleVerseOfTheDay_bibleVerse))
	{
		$bibleVerseOfTheDay_bibleVerse = '';
		$bibleVerseOfTheDay_Date = '';
	}	

	if($bibleVerseOfTheDay_Date != $bibleVerseOfTheDay_currentDate && $bibleVerseOfTheDay_lastAttempt < (date('U') - 3600))
	{
		$url = 'http://dailyverses.net/get/verse?language=' . $language . '&date=' . $bibleVerseOfTheDay_currentDate . '&url=' . $_SERVER['HTTP_HOST'] . '&type=daily2_7_4';
		$result = wp_remote_get($url);

		update_option('bibleVerseOfTheDay_LastAttempt' . $languageAdd, date('U'));
		
		if(!is_wp_error($result)) 
		{
			$response = str_replace(',', '&#44;', $result['body']);

			if (is_valid_verse($response)) 
			{
				$bibleVerseOfTheDay_bibleVerse = $response;
				update_option('bibleVerseOfTheDay_Date' . $languageAdd, $bibleVerseOfTheDay_currentDate);
				update_option('bibleVerseOfTheDay_Verse' . $languageAdd, $bibleVerseOfTheDay_bibleVerse);
			}
		}
	}

	if($bibleVerseOfTheDay_bibleVerse == '')
	{
		$bibleVerseOfTheDay_bibleVerse = get_default_verse($language);
	}

    if($showlink == 'true' || $showlink == '1')
	{
		$html =  $bibleVerseOfTheDay_bibleVerse . '<div class="dailyVerses linkToWebsite"><a href="https://dailyverses.net' . $languageUrl . '" target="_blank" rel="noopener">DailyVerses.net</a></div>';
	}
	else
	{
		$html = $bibleVerseOfTheDay_bibleVerse;
	}
	
	return $html;
}

function random_bible_verse($showlink, $language='niv', $isblock=false) 
{
	if (!$isblock)
	{
		wp_register_style( 'prefix-style', plugins_url('bible-verse-of-the-day.css', __FILE__) );
		wp_enqueue_style( 'prefix-style' );
	}

	if ($language == '')
	{
		$language == 'niv';
	}
	
	$languageAdd = get_language_add($language);
	$languageUrl = get_language_url($language);

	$position = rand(0, 400);
	$randomBibleVerse = get_option('randomBibleVerse_' . $position . $languageAdd);
	$randomBibleVerse_lastAttempt = get_option('randomBibleVerse_LastAttempt' . $languageAdd);
	
	if (!is_valid_verse($randomBibleVerse))
	{
		$randomBibleVerse = '';
	}

	if($randomBibleVerse == "" && $randomBibleVerse_lastAttempt < (date('U') - 3600))
	{
		$url = 'http://dailyverses.net/get/random?language=' . $language . '&position=' . $position . '&url=' . $_SERVER['HTTP_HOST'] . '&type=random2_7_4';
		$result = wp_remote_get($url);

		if(!is_wp_error($result)) 
		{
			$response = str_replace(',', '&#44;', $result['body']);
	
			if (is_valid_verse($response)) 
			{
				$randomBibleVerse = $response;
				update_option('randomBibleVerse_' . $position . $languageAdd, $randomBibleVerse);
			}
			else
			{
				update_option('randomBibleVerse_LastAttempt' . $languageAdd, date('U'));
			}
		}
		else
		{
			update_option('randomBibleVerse_LastAttempt' . $languageAdd, date('U'));
		}
	}

	if($randomBibleVerse == '')
	{
		$randomBibleVerse = get_default_verse($language);
	}
		
	if($showlink == 'true' || $showlink == '1')
	{
		$html = $randomBibleVerse . '<div class="dailyVerses linkToWebsite"><a href="https://dailyverses.net' . $languageUrl . '" target="_blank" rel="noopener">DailyVerses.net</a></div>';
	}
	else
	{
		$html = $randomBibleVerse;
	}
	
	return $html;
}

function is_valid_verse($string) 
{ 
	$startString = '<div class="dailyVerses bibleText">';
	$len = strlen($startString); 
	
	if ($string == '' || strlen($string) < $len)
	{
		return false;
	}	

	return (substr($string, 0, $len) === $startString); 
} 

function get_default_verse($language)
{
	if($language == "kjv")
	{
		return '<div class="dailyVerses bibleText">For God so loved the world, that he gave his only begotten Son, that whosoever believeth in him should not perish, but have everlasting life.</div><div class="dailyVerses bibleVerse"><a href="https://dailyverses.net/john/3/16/kjv" target="_blank" rel="noopener">John 3:16</a></div>';
	}
	else if($language == "esv")
	{
		return '<div class="dailyVerses bibleText">For God so loved the world, that he gave his only Son, that whoever believes in him should not perish but have eternal life.</div><div class="dailyVerses bibleVerse"><a href="https://dailyverses.net/john/3/16/esv" target="_blank" rel="noopener">John 3:16</a></div>';
	}
	else if($language == "nkjv")
	{
		return '<div class="dailyVerses bibleText">For God so loved the world that He gave His only begotten Son, that whoever believes in Him should not perish but have everlasting life.</div><div class="dailyVerses bibleVerse"><a href="https://dailyverses.net/john/3/16/nkjv" target="_blank" rel="noopener">John 3:16</a></div>';
	}
	else if($language == "nlt")
	{
		return '<div class="dailyVerses bibleText">For this is how God loved the world: He gave his one and only Son, so that everyone who believes in him will not perish but have eternal life.</div><div class="dailyVerses bibleVerse"><a href="https://dailyverses.net/john/3/16/nlt" target="_blank" rel="noopener">John 3:16</a></div>';
	}
	else if($language == "nrsv")
	{
		return '<div class="dailyVerses bibleText">For God so loved the world that he gave his only Son, so that everyone who believes in him may not perish but may have eternal life.</div><div class="dailyVerses bibleVerse"><a href="https://dailyverses.net/john/3/16/nrsv" target="_blank" rel="noopener">John 3:16</a></div>';
	}
	else if($language == "web")
	{
		return '<div class="dailyVerses bibleText">For God so loved the world, that he gave his only born Son, that whoever believes in him should not perish, but have eternal life.</div><div class="dailyVerses bibleVerse"><a href="https://dailyverses.net/john/3/16/web" target="_blank" rel="noopener">John 3:16</a></div>';
	}
	else if($language == "ncb")
	{
		return '<div class="dailyVerses bibleText">For God so loved the world that he gave his only Son, so that everyone who believes in him may not perish but may attain eternal life.</div><div class="dailyVerses bibleVerse"><a href="https://dailyverses.net/john/3/16/ncb" target="_blank" rel="noopener">John 3:16</a></div>';
	}
	else if($language == "nl" || $language == "nbv")
	{
		return '<div class="dailyVerses bibleText">Want God had de wereld zo lief dat Hij zijn enige Zoon heeft gegeven, opdat iedereen die in Hem gelooft niet verloren gaat, maar eeuwig leven heeft.</div><div class="dailyVerses bibleVerse"><a href="https://dailyverses.net/nl/johannes/3/16" target="_blank" rel="noopener">Johannes 3:16</a></div>';
	}
	else if($language == "bgt")
	{
		return '<div class="dailyVerses bibleText">Want Gods liefde voor de mensen was zo groot, dat hij zijn enige Zoon gegeven heeft. Iedereen die in hem gelooft, zal niet sterven, maar voor altijd leven.</div><div class="dailyVerses bibleVerse"><a href="https://dailyverses.net/nl/johannes/3/16/bgt" target="_blank" rel="noopener">Johannes 3:16</a></div>';
	}
	else if($language == "nbg")
	{
		return '<div class="dailyVerses bibleText">Want alzo lief heeft God de wereld gehad, dat Hij zijn eniggeboren Zoon gegeven heeft, opdat een ieder, die in Hem gelooft, niet verloren ga, maar eeuwig leven hebbe.</div><div class="dailyVerses bibleVerse"><a href="https://dailyverses.net/nl/johannes/3/16/nbg" target="_blank" rel="noopener">Johannes 3:16</a></div>';
	}
	else if($language == "bb")
	{
		return '<div class="dailyVerses bibleText">Want God houdt zoveel van de mensen, dat Hij zijn enige Zoon aan hen heeft gegeven. Iedereen die in Hem gelooft, zal niet verloren gaan, maar zal het eeuwige leven hebben.</div><div class="dailyVerses bibleVerse"><a href="https://dailyverses.net/nl/johannes/3/16/bb" target="_blank" rel="noopener">Johannes 3:16</a></div>';
	}
	else if($language == "hsv")
	{
		return '<div class="dailyVerses bibleText">Want zo lief heeft God de wereld gehad, dat Hij Zijn eniggeboren Zoon gegeven heeft, opdat ieder die in Hem gelooft, niet verloren gaat, maar eeuwig leven heeft.</div><div class="dailyVerses bibleVerse"><a href="https://dailyverses.net/nl/johannes/3/16/hsv" target="_blank" rel="noopener">Johannes 3:16</a></div>';
	}
	else if($language == "es" || $language == "nvi")
	{
		return '<div class="dailyVerses bibleText">Porque tanto amó Dios al mundo que dio a su Hijo unigénito, para que todo el que cree en él no se pierda, sino que tenga vida eterna.</div><div class="dailyVerses bibleVerse"><a href="https://dailyverses.net/es/juan/3/16" target="_blank" rel="noopener">Juan 3:16</a></div>';
	}
	else if($language == "rvr95")
	{
		return '<div class="dailyVerses bibleText">De tal manera amó Dios al mundo, que ha dado a su Hijo unigénito, para que todo aquel que en él cree no se pierda, sino que tenga vida eterna.</div><div class="dailyVerses bibleVerse"><a href="https://dailyverses.net/es/juan/3/16/rvr95" target="_blank" rel="noopener">Juan 3:16</a></div>';
	}
	else if($language == "rvr60")
	{
		return '<div class="dailyVerses bibleText">Porque de tal manera amó Dios al mundo, que ha dado a su Hijo unigénito, para que todo aquel que en él cree, no se pierda, mas tenga vida eterna.</div><div class="dailyVerses bibleVerse"><a href="https://dailyverses.net/es/juan/3/16/rvr60" target="_blank" rel="noopener">Juan 3:16</a></div>';
	}
	else if($language == "lbla")
	{
		return '<div class="dailyVerses bibleText">Porque de tal manera amó Dios al mundo, que dio a su Hijo unigénito, para que todo aquel que cree en Él, no se pierda, mas tenga vida eterna.</div><div class="dailyVerses bibleVerse"><a href="https://dailyverses.net/es/juan/3/16/lbla" target="_blank" rel="noopener">Juan 3:16</a></div>';
	}
	else if($language == "de" || $language == "lut")
	{
		return '<div class="dailyVerses bibleText">Denn also hat Gott die Welt geliebt, dass er seinen eingeborenen Sohn gab, auf dass alle, die an ihn glauben, nicht verloren werden, sondern das ewige Leben haben.</div><div class="dailyVerses bibleVerse"><a href="https://dailyverses.net/de/johannes/3/16" target="_blank" rel="noopener">Johannes 3:16</a></div>';
	}
	else if($language == "neu")
	{
		return '<div class="dailyVerses bibleText">Denn so hat Gott der Welt seine Liebe gezeigt: Er gab seinen einzigen Sohn, damit jeder, der an ihn glaubt, nicht ins Verderben geht, sondern ewiges Leben hat.</div><div class="dailyVerses bibleVerse"><a href="https://dailyverses.net/de/johannes/3/16/neu" target="_blank" rel="noopener">Johannes 3:16</a></div>';
	}
	else if($language == "elb")
	{
		return '<div class="dailyVerses bibleText">Denn so hat Gott die Welt geliebt, dass er seinen einzigen Sohn gab, damit jeder, der an ihn glaubt, nicht verloren geht, sondern ewiges Leben hat.</div><div class="dailyVerses bibleVerse"><a href="https://dailyverses.net/de/johannes/3/16/elb" target="_blank" rel="noopener">Johannes 3:16</a></div>';
	}
	else if($language == "lu12")
	{
		return '<div class="dailyVerses bibleText">Also hat Gott die Welt geliebt, daß er seinen eingeborenen Sohn gab, auf daß alle, die an ihn glauben, nicht verloren werden, sondern das ewige Leben haben.</div><div class="dailyVerses bibleVerse"><a href="https://dailyverses.net/de/johannes/3/16/lu12" target="_blank" rel="noopener">Johannes 3:16</a></div>';
	}
	else if($language == "pt" || $language == "arc")
	{
		return '<div class="dailyVerses bibleText">Porque Deus amou o mundo de tal maneira que deu o seu Filho unigênito, para que todo aquele que nele crê não pereça, mas tenha a vida eterna.</div><div class="dailyVerses bibleVerse"><a href="https://dailyverses.net/pt/joao/3/16" target="_blank" rel="noopener">João 3:16</a></div>';
	}
	else if($language == "nvi-pt")
	{
		return '<div class="dailyVerses bibleText">Porque Deus tanto amou o mundo que deu o seu Filho Unigênito, para que todo o que nele crer não pereça, mas tenha a vida eterna.</div><div class="dailyVerses bibleVerse"><a href="https://dailyverses.net/pt/joao/3/16/nvi-pt" target="_blank" rel="noopener">João 3:16</a></div>';
	}
	else if($language == "ara")
	{
		return '<div class="dailyVerses bibleText">Porque Deus amou ao mundo de tal maneira que deu o seu Filho unigênito, para que todo o que nele crê não pereça, mas tenha a vida eterna.</div><div class="dailyVerses bibleVerse"><a href="https://dailyverses.net/pt/joao/3/16/ara" target="_blank" rel="noopener">João 3:16</a></div>';
	}
	else if($language == "sk" || $language == "kat")
	{
		return '<div class="dailyVerses bibleText">Veď Boh tak miloval svet, že dal svojho jednorodeného Syna, aby nezahynul nik, kto v neho verí, ale aby mal večný život.</div><div class="dailyVerses bibleVerse"><a href="https://dailyverses.net/sk/jan/3/16" target="_blank" rel="noopener">Ján 3:16</a></div>';
	}
	else if($language == "it" || $language == "cei")
	{
		return '<div class="dailyVerses bibleText">Dio infatti ha tanto amato il mondo da dare il suo Figlio unigenito, perché chiunque crede in lui non muoia, ma abbia la vita eterna.</div><div class="dailyVerses bibleVerse"><a href="https://dailyverses.net/it/giovanni/3/16" target="_blank" rel="noopener">Giovanni 3:16</a></div>';
	}
	else if($language == "nr06")
	{
		return '<div class="dailyVerses bibleText">Perché Dio ha tanto amato il mondo, che ha dato il suo unigenito Figlio, affinché chiunque crede in lui non perisca, ma abbia vita eterna.</div><div class="dailyVerses bibleVerse"><a href="https://dailyverses.net/it/giovanni/3/16/nr06" target="_blank" rel="noopener">Giovanni 3:16</a></div>';
	}
	else if($language == "fr" || $language == "sg21")
	{
		return '<div class="dailyVerses bibleText">En effet, Dieu a tant aimé le monde qu&apos;il a donné son Fils unique afin que quiconque croit en lui ne périsse pas mais ait la vie éternelle.</div><div class="dailyVerses bibleVerse"><a href="https://dailyverses.net/fr/jean/3/16" target="_blank" rel="noopener">Jean 3:16</a></div>';
	}
	else if($language == "bds")
	{
		return '<div class="dailyVerses bibleText">Oui, Dieu a tant aimé le monde qu’il a donné son Fils, son unique, pour que tous ceux qui placent leur confiance en lui échappent à la perdition et qu’ils aient la vie éternelle.</div><div class="dailyVerses bibleVerse"><a href="https://dailyverses.net/fr/jean/3/16/bds" target="_blank" rel="noopener">Jean 3:16</a></div>';
	}
	else if($language == "fi" || $language == "kr92")
	{
		return '<div class="dailyVerses bibleText">Jumala on rakastanut maailmaa niin paljon, että antoi ainoan Poikansa, jottei yksikään, joka häneen uskoo, joutuisi kadotukseen, vaan saisi iankaikkisen elämän.</div><div class="dailyVerses bibleVerse"><a href="https://dailyverses.net/fi/johannes/3/16" target="_blank" rel="noopener">Johannes 3:16</a></div>';
	}
	else if($language == "cuvs")
	{
		return '<div class="dailyVerses bibleText">神 爱 世 人 ， 甚 至 将 他 的 独 生 子 赐 给 他 们 ， 叫 一 切 信 他 的 ， 不 至 灭 亡 ， 反 得 永 生 。</div><div class="dailyVerses bibleVerse"><a href="https://dailyverses.net/tc/%e7%b4%84%e7%bf%b0%e7%a6%8f%e9%9f%b3/3/16/cuvs" target="_blank" rel="noopener">约 翰 福 音 3:16</a></div>';
	}
	else if($language == "cuv")
	{
		return '<div class="dailyVerses bibleText">神 愛 世 人 ， 甚 至 將 他 的 獨 生 子 賜 給 他 們 ， 叫 一 切 信 他 的 ， 不 至 滅 亡 ， 反 得 永 生 。</div><div class="dailyVerses bibleVerse"><a href="https://dailyverses.net/tc/%e7%b4%84%e7%bf%b0%e7%a6%8f%e9%9f%b3/3/16" target="_blank" rel="noopener">約 翰 福 音 3:16</a></div>';
	}
	else if($language == "avd")
	{
		return '<div class="dailyVerses bibleText">لِأَنَّهُ هَكَذَا أَحَبَّ ٱللهُ ٱلْعَالَمَ حَتَّى بَذَلَ ٱبْنَهُ ٱلْوَحِيدَ، لِكَيْ لَا يَهْلِكَ كُلُّ مَنْ يُؤْمِنُ بِهِ، بَلْ تَكُونُ لَهُ ٱلْحَيَاةُ ٱلْأَبَدِيَّةُ.</div><div class="dailyVerses bibleVerse"><a href="https://dailyverses.net/ar/%d9%8a%d9%8f%d9%88%d8%ad%d9%8e%d9%86%d9%91%d9%8e%d8%a7/3/16" target="_blank" rel="noopener">يُوحَنَّا ٣:‏١٦</a></div>';
	}
	else if($language == "keh")
	{
		return '<div class="dailyVerses bibleText">لأَنَّهُ هكَذَا أَحَبَّ اللهُ الْعَالَمَ حَتَّى بَذَلَ ابْنَهُ الْوَحِيدَ، لِكَيْ لَا يَهْلِكَ كُلُّ مَنْ يُؤْمِنُ بِهِ، بَلْ تَكُونُ لَهُ الْحَيَاةُ الأَبَدِيَّةُ.</div><div class="dailyVerses bibleVerse"><a href="https://dailyverses.net/ar/%d9%8a%d9%8f%d9%88%d8%ad%d9%8e%d9%86%d9%91%d9%8e%d8%a7/3/16/keh" target="_blank" rel="noopener">يُوحَنَّا ٣:‏١٦</a></div>';
	}
	else if($language == "cep")
	{
		return '<div class="dailyVerses bibleText">Neboť Bůh tak miloval svět, že dal svého jediného Syna, aby žádný, kdo v něho věří, nezahynul, ale měl život věčný.</div><div class="dailyVerses bibleVerse"><a href="https://dailyverses.net/cs/jan/3/16" target="_blank" rel="noopener">Jan 3:16</a></div>';
	}
	else if($language == "b21")
	{
		return '<div class="dailyVerses bibleText">Neboť Bůh tak miloval svět, že dal svého jednorozeného Syna, aby žádný, kdo v něj věří, nezahynul, ale měl věčný život.</div><div class="dailyVerses bibleVerse"><a href="https://dailyverses.net/cs/jan/3/16/b21" target="_blank" rel="noopener">Jan 3:16</a></div>';
	}
	else if($language == "rst")
	{
		return '<div class="dailyVerses bibleText">Ибо так возлюбил Бог мир, что отдал Сына Своего Единородного, дабы всякий верующий в Него, не погиб, но имел жизнь вечную.</div><div class="dailyVerses bibleVerse"><a href="https://dailyverses.net/ru/%d0%be%d1%82-%d0%b8%d0%be%d0%b0%d0%bd%d0%bd%d0%b0/3/16" target="_blank" rel="noopener">От Иоанна 3:16</a></div>';
	}
	else if($language == "afr53")
	{
		return '<div class="dailyVerses bibleText">Want so lief het God die wêreld gehad, dat Hy sy eniggebore Seun gegee het, sodat elkeen wat in Hom glo, nie verlore mag gaan nie, maar die ewige lewe kan hê.</div><div class="dailyVerses bibleVerse"><a href="https://dailyverses.net/af/johannes/3/16" target="_blank" rel="noopener">Johannes 3:16</a></div>';
	}
	else if($language == "ubg")
	{
		return '<div class="dailyVerses bibleText">Tak bowiem Bóg umiłował świat, że dał swego jednorodzonego Syna, aby każdy, kto w niego wierzy, nie zginął, ale miał życie wieczne.</div><div class="dailyVerses bibleVerse"><a href="https://dailyverses.net/pl/jana/3/16" target="_blank" rel="noopener">Jana 3:16</a></div>';
	}
	else if($language == "tgv")
	{
		return '<div class="dailyVerses bibleText">Τόσο πολύ αγάπησε ο Θεός τον κόσμο, ώστε παρέδωσε στο θάνατο το μονογενή του Υιό, για να μη χαθεί όποιος πιστεύει σ’ αυτόν αλλά να έχει ζωή αιώνια.</div><div class="dailyVerses bibleVerse"><a href="https://dailyverses.net/el/%ce%ba%ce%b1%cf%84%ce%b1-%ce%b9%cf%89%ce%b1%ce%bd%ce%bd%ce%b7%ce%bd/3/16" target="_blank" rel="noopener">ΚΑΤΑ ΙΩΑΝΝΗΝ 3:16</a></div>';
	}
	else if($language == "pcb")
	{
		return '<div class="dailyVerses bibleText">زيرا خدا به قدری مردم جهان را دوست دارد كه يگانه فرزند خود را فرستاده است، تا هر كه به او ايمان آورد، هلاک نشود بلكه زندگی جاويد بيابد.</div><div class="dailyVerses bibleVerse"><a href="https://dailyverses.net/fa/%d9%8a%d9%88%d8%ad%d9%86%d8%a7/3/16" target="_blank" rel="noopener">يوحنا ۳:‏۱۶</a></div>';
	}
	else if($language == "zul59")
	{
		return '<div class="dailyVerses bibleText">Ngokuba uNkulunkulu walithanda izwe kangaka, waze wanikela ngeNdodana yakhe ezelwe yodwa ukuba yilowo nalowo okholwa yiyo angabhubhi, kodwa abe nokuphila okuphakade.</div><div class="dailyVerses bibleVerse"><a href="https://dailyverses.net/zu/ngokukajohane/3/16" target="_blank" rel="noopener">NgokukaJohane 3:16</a></div>';
	}
	else if($language == "sso89")
	{
		return '<div class="dailyVerses bibleText">Modimo o ratile lefatshe hoo a le neileng Mora wa hae ya tswetsweng a inotshi, hore e mong le e mong ya dumelang ho yena, a se ke a timela, a mpe a be le bophelo bo sa feleng.</div><div class="dailyVerses bibleVerse"><a href="https://dailyverses.net/st/johanne/3/16" target="_blank" rel="noopener">JOHANNE 3:16</a></div>';
	}
	else if($language == "xho96")
	{
		return '<div class="dailyVerses bibleText">Kaloku uThixo ihlabathi ulithande kangangokuba ude wancama uNyana okuphela kwakhe, ukuze wonke umntu ozinikele kuye ngokupheleleyo angatshabalali, koko abe nobona bomi bungenasiphelo.</div><div class="dailyVerses bibleVerse"><a href="https://dailyverses.net/xh/uyohane/3/16" target="_blank" rel="noopener">UYOHANE 3:16</a></div>';
	}
	else if($language == "rovu")
	{
		return '<div class="dailyVerses bibleText">কারণ ঈশ্বর জগৎকে এমন প্রেম করিলেন যে, আপনার এক জাত পুত্রকে দান করিলেন, যেন, যে কেহ তাঁহাতে বিশ্বাস করে, সে বিনষ্ট না হয়, কিন্তু অনন্ত জীবন পায়।</div><div class="dailyVerses bibleVerse"><a href="https://dailyverses.net/bn/%e0%a6%af%e0%a7%8b%e0%a6%b9%e0%a6%a8/3/16" target="_blank" rel="noopener">যোহন ৩:১৬</a></div>';
	}
	else if($language == "urd")
	{
		return '<div class="dailyVerses bibleText">کیونکہ خُدا نے دُنیا سے اَیسی مُحبّت رکھّی کہ اُس نے اپنا اِکلَوتا بیٹا بخش دِیا تاکہ جو کوئی اُس پر اِیمان لائے ہلاک نہ ہو بلکہ ہمیشہ کی زِندگی پائے۔</div><div class="dailyVerses bibleVerse"><a href="https://dailyverses.net/ur/%db%8c%d9%8f%d9%88%d8%ad%d9%86%d9%91%d8%a7/3/16" target="_blank" rel="noopener">یُوحنّا 3:‏16</a></div>';
	}
	else if($language == "hhbd")
	{
		return '<div class="dailyVerses bibleText">क्योंकि परमेश्वर ने जगत से ऐसा प्रेम रखा कि उस ने अपना एकलौता पुत्र दे दिया, ताकि जो कोई उस पर विश्वास करे, वह नाश न हो, परन्तु अनन्त जीवन पाए।</div><div class="dailyVerses bibleVerse"><a href="https://dailyverses.net/hi/%e0%a4%af%e0%a5%82%e0%a4%b9%e0%a4%a8%e0%a5%8d%e0%a4%a8%e0%a4%be/3/16" target="_blank" rel="noopener">यूहन्ना 3:16</a></div>';
	}
	else if($language == "bdan")
	{
		return '<div class="dailyVerses bibleText">Gud elskede nemlig verden så højt, at han gav sin eneste Søn, for at enhver, der tror på ham, ikke skal gå fortabt, men få det evige liv.</div><div class="dailyVerses bibleVerse"><a href="https://dailyverses.net/da/johannesevangeliet/3/16" target="_blank" rel="noopener">Johannesevangeliet 3:16</a></div>';
	}
	else if($language == "da1871")
	{
		return '<div class="dailyVerses bibleText">Thi saaledes elskede Gud Verden, at han gav sin Søn den enbaarne, for at hver den, som tror paa ham, ikke skal fortabes, men have et evigt Liv.</div><div class="dailyVerses bibleVerse"><a href="https://dailyverses.net/da/johannesevangeliet/3/16/da1871" target="_blank" rel="noopener">Johannesevangeliet 3:16</a></div>';
	}
	else if($language == "mg1865")
	{
		return '<div class="dailyVerses bibleText">Fa toy izao no nitiavan’Andriamanitra izao tontolo izao: nomeny ny Zanani-lahy Tokana, mba tsy ho very izay rehetra mino Azy, fa hanana fiainana mandrakizay.</div><div class="dailyVerses bibleVerse"><a href="https://dailyverses.net/mg/jaona/3/16" target="_blank" rel="noopener">Jaona 3:16</a></div>';
	}

	//niv
	return '<div class="dailyVerses bibleText">For God so loved the world that he gave his one and only Son, that whoever believes in him shall not perish but have eternal life.</div><div class="dailyVerses bibleVerse"><a href="https://dailyverses.net/john/3/16" target="_blank" rel="noopener">John 3:16</a></div>';
}

function get_language_add($language)
{
	return '_' . $language;
}

function get_language_url($language)
{
	if($language == 'nl' || $language == 'nbv' || $language == 'bgt' || $language == 'nbg' || $language == 'bb' || $language == 'hsv')
	{
		return '/nl';
	}
	else if($language == 'es' || $language == 'nvi' || $language == 'rvr95' || $language == 'rvr60' || $language == 'lbla')
	{
		return '/es';
	}
	else if($language == 'de' || $language == 'lut' || $language == 'neu' || $language == 'elb' || $language == 'lu12')
	{
		return '/de';
	}
	else if($language == 'pt' || $language == 'arc' || $language == 'nvi-pt' || $language == 'ara')
	{
		return '/pt';
	}
	else if($language == 'sk' || $language == 'kat')
	{
		return '/sk';
	}
	else if($language == 'it' || $language == 'cei' || $language == 'nr06')
	{
		return '/it';
	}
	else if($language == 'fr' || $language == 'sg21' || $language == 'bds')
	{
		return '/fr';
	}
	else if($language == 'fi' || $language == 'kr92')
	{
		return '/fi';
	}
	else if($language == 'cuvs' || $language == 'cuv')
	{
		return '/tc';
	}
	else if($language == 'avd' || $language == 'keh')
	{
		return '/ar';
	}
	else if($language == 'cep' || $language == 'b21')
	{
		return '/cs';
	}
	else if($language == 'rst')
	{
		return '/ru';
	}
	else if($language == 'afr53')
	{
		return '/af';
	}
	else if($language == 'ubg')
	{
		return '/pl';
	}
	else if($language == 'tgv')
	{
		return '/el';
	}
	else if($language == 'pcb')
	{
		return '/fa';
	}
	else if($language == 'zul59')
	{
		return '/zu';
	}
	else if($language == 'sso89')
	{
		return '/st';
	}
	else if($language == 'xho96')
	{
		return '/xh';
	}
	else if($language == 'rovu')
	{
		return '/bn';
	}
	else if($language == 'urd')
	{
		return '/ur';
	}
	else if($language == 'hhbd')
	{
		return '/hi';
	}
	else if($language == 'bdan' || $language == 'da1871')
	{
		return '/da';
	}
	else if($language == 'mg1865')
	{
		return '/mg';
	}
	
	return '';
}

//en
add_shortcode('bibleverseoftheday', 'bible_verse_of_the_day_niv'); 
add_shortcode('randombibleverse', 'random_bible_verse_niv'); 
add_shortcode('bibleverseoftheday_en', 'bible_verse_of_the_day_niv'); 
add_shortcode('randombibleverse_en', 'random_bible_verse_niv'); 
add_shortcode('bibleverseoftheday_niv', 'bible_verse_of_the_day_niv'); 
add_shortcode('randombibleverse_niv', 'random_bible_verse_niv'); 
add_shortcode('bibleverseoftheday_kjv', 'bible_verse_of_the_day_kjv'); 
add_shortcode('randombibleverse_kjv', 'random_bible_verse_kjv'); 
add_shortcode('bibleverseoftheday_esv', 'bible_verse_of_the_day_esv'); 
add_shortcode('randombibleverse_esv', 'random_bible_verse_esv'); 
add_shortcode('bibleverseoftheday_nkjv', 'bible_verse_of_the_day_nkjv'); 
add_shortcode('randombibleverse_nkjv', 'random_bible_verse_nkjv'); 
add_shortcode('bibleverseoftheday_nlt', 'bible_verse_of_the_day_nlt'); 
add_shortcode('randombibleverse_nlt', 'random_bible_verse_nlt'); 
add_shortcode('bibleverseoftheday_nrsv', 'bible_verse_of_the_day_nrsv'); 
add_shortcode('randombibleverse_nrsv', 'random_bible_verse_nrsv'); 
add_shortcode('bibleverseoftheday_web', 'bible_verse_of_the_day_web'); 
add_shortcode('randombibleverse_web', 'random_bible_verse_web'); 
add_shortcode('bibleverseoftheday_ncb', 'bible_verse_of_the_day_ncb'); 
add_shortcode('randombibleverse_ncb', 'random_bible_verse_ncb'); 

//nl
add_shortcode('bibleverseoftheday_nl', 'bible_verse_of_the_day_nbv'); 
add_shortcode('randombibleverse_nl', 'random_bible_verse_nbv'); 
add_shortcode('bibleverseoftheday_nbv', 'bible_verse_of_the_day_nbv'); 
add_shortcode('randombibleverse_nbv', 'random_bible_verse_nbv'); 
add_shortcode('bibleverseoftheday_bgt', 'bible_verse_of_the_day_bgt'); 
add_shortcode('randombibleverse_bgt', 'random_bible_verse_bgt'); 
add_shortcode('bibleverseoftheday_nbg', 'bible_verse_of_the_day_nbg'); 
add_shortcode('randombibleverse_nbg', 'random_bible_verse_nbg'); 
add_shortcode('bibleverseoftheday_bb', 'bible_verse_of_the_day_bb'); 
add_shortcode('randombibleverse_bb', 'random_bible_verse_bb'); 
add_shortcode('bibleverseoftheday_hsv', 'bible_verse_of_the_day_hsv'); 
add_shortcode('randombibleverse_hsv', 'random_bible_verse_hsv'); 

//es
add_shortcode('bibleverseoftheday_es', 'bible_verse_of_the_day_nvi'); 
add_shortcode('randombibleverse_es', 'random_bible_verse_nvi'); 
add_shortcode('bibleverseoftheday_nvi', 'bible_verse_of_the_day_nvi'); 
add_shortcode('randombibleverse_nvi', 'random_bible_verse_nvi'); 
add_shortcode('bibleverseoftheday_rvr95', 'bible_verse_of_the_day_rvr95'); 
add_shortcode('randombibleverse_rvr95', 'random_bible_verse_rvr95'); 
add_shortcode('bibleverseoftheday_rvr60', 'bible_verse_of_the_day_rvr60'); 
add_shortcode('randombibleverse_rvr60', 'random_bible_verse_rvr60'); 
add_shortcode('bibleverseoftheday_lbla', 'bible_verse_of_the_day_lbla'); 
add_shortcode('randombibleverse_lbla', 'random_bible_verse_lbla'); 

//de
add_shortcode('bibleverseoftheday_de', 'bible_verse_of_the_day_lut'); 
add_shortcode('randombibleverse_de', 'random_bible_verse_lut'); 
add_shortcode('bibleverseoftheday_lut', 'bible_verse_of_the_day_lut'); 
add_shortcode('randombibleverse_lut', 'random_bible_verse_lut'); 
add_shortcode('bibleverseoftheday_neu', 'bible_verse_of_the_day_neu'); 
add_shortcode('randombibleverse_neu', 'random_bible_verse_neu'); 
add_shortcode('bibleverseoftheday_elb', 'bible_verse_of_the_day_elb'); 
add_shortcode('randombibleverse_elb', 'random_bible_verse_elb'); 
add_shortcode('bibleverseoftheday_lu12', 'bible_verse_of_the_day_lu12'); 
add_shortcode('randombibleverse_lu12', 'random_bible_verse_lu12'); 

//pt
add_shortcode('bibleverseoftheday_pt', 'bible_verse_of_the_day_arc'); 
add_shortcode('randombibleverse_pt', 'random_bible_verse_arc'); 
add_shortcode('bibleverseoftheday_arc', 'bible_verse_of_the_day_arc'); 
add_shortcode('randombibleverse_arc', 'random_bible_verse_arc'); 
add_shortcode('bibleverseoftheday_nvi_pt', 'bible_verse_of_the_day_nvi_pt'); 
add_shortcode('randombibleverse_nvi_pt', 'random_bible_verse_nvi_pt'); 
add_shortcode('bibleverseoftheday_ara', 'bible_verse_of_the_day_ara'); 
add_shortcode('randombibleverse_ara', 'random_bible_verse_ara'); 

//sk
add_shortcode('bibleverseoftheday_sk', 'bible_verse_of_the_day_kat'); 
add_shortcode('randombibleverse_sk', 'random_bible_verse_kat'); 
add_shortcode('bibleverseoftheday_kat', 'bible_verse_of_the_day_kat'); 
add_shortcode('randombibleverse_kat', 'random_bible_verse_kat'); 

//it
add_shortcode('bibleverseoftheday_it', 'bible_verse_of_the_day_cei'); 
add_shortcode('randombibleverse_it', 'random_bible_verse_cei'); 
add_shortcode('bibleverseoftheday_cei', 'bible_verse_of_the_day_cei'); 
add_shortcode('randombibleverse_cei', 'random_bible_verse_cei'); 
add_shortcode('bibleverseoftheday_nr06', 'bible_verse_of_the_day_nr06'); 
add_shortcode('randombibleverse_nr06', 'random_bible_verse_nr06'); 

//fr
add_shortcode('bibleverseoftheday_fr', 'bible_verse_of_the_day_sg21'); 
add_shortcode('randombibleverse_fr', 'random_bible_verse_sg21'); 
add_shortcode('bibleverseoftheday_sg21', 'bible_verse_of_the_day_sg21'); 
add_shortcode('randombibleverse_sg21', 'random_bible_verse_sg21'); 
add_shortcode('bibleverseoftheday_bds', 'bible_verse_of_the_day_bds'); 
add_shortcode('randombibleverse_bds', 'random_bible_verse_bds'); 

//fi
add_shortcode('bibleverseoftheday_fi', 'bible_verse_of_the_day_kr92'); 
add_shortcode('randombibleverse_fi', 'random_bible_verse_kr92'); 
add_shortcode('bibleverseoftheday_kr92', 'bible_verse_of_the_day_kr92'); 
add_shortcode('randombibleverse_kr92', 'random_bible_verse_kr92'); 

//tc
add_shortcode('bibleverseoftheday_cuvs', 'bible_verse_of_the_day_cuvs'); 
add_shortcode('randombibleverse_cuvs', 'random_bible_verse_cuvs'); 
add_shortcode('bibleverseoftheday_cuv', 'bible_verse_of_the_day_cuv'); 
add_shortcode('randombibleverse_cuv', 'random_bible_verse_cuv'); 

//ar
add_shortcode('bibleverseoftheday_avd', 'bible_verse_of_the_day_avd'); 
add_shortcode('randombibleverse_avd', 'random_bible_verse_avd'); 
add_shortcode('bibleverseoftheday_keh', 'bible_verse_of_the_day_keh'); 
add_shortcode('randombibleverse_keh', 'random_bible_verse_keh'); 

//cs
add_shortcode('bibleverseoftheday_cep', 'bible_verse_of_the_day_cep'); 
add_shortcode('randombibleverse_cep', 'random_bible_verse_cep'); 
add_shortcode('bibleverseoftheday_b21', 'bible_verse_of_the_day_b21'); 
add_shortcode('randombibleverse_b21', 'random_bible_verse_b21'); 

//ru
add_shortcode('bibleverseoftheday_rst', 'bible_verse_of_the_day_rst'); 
add_shortcode('randombibleverse_rst', 'random_bible_verse_rst'); 

//af
add_shortcode('bibleverseoftheday_afr53', 'bible_verse_of_the_day_afr53'); 
add_shortcode('randombibleverse_afr53', 'random_bible_verse_afr53'); 

//pl
add_shortcode('bibleverseoftheday_ubg', 'bible_verse_of_the_day_ubg'); 
add_shortcode('randombibleverse_ubg', 'random_bible_verse_ubg'); 

//el
add_shortcode('bibleverseoftheday_tgv', 'bible_verse_of_the_day_tgv'); 
add_shortcode('randombibleverse_tgv', 'random_bible_verse_tgv'); 

//fa
add_shortcode('bibleverseoftheday_pcb', 'bible_verse_of_the_day_pcb'); 
add_shortcode('randombibleverse_pcb', 'random_bible_verse_pcb'); 

//zu
add_shortcode('bibleverseoftheday_zul59', 'bible_verse_of_the_day_zul59'); 
add_shortcode('randombibleverse_zul59', 'random_bible_verse_zul59'); 

//st
add_shortcode('bibleverseoftheday_sso89', 'bible_verse_of_the_day_sso89'); 
add_shortcode('randombibleverse_sso89', 'random_bible_verse_sso89'); 

//xh
add_shortcode('bibleverseoftheday_xho96', 'bible_verse_of_the_day_xho96'); 
add_shortcode('randombibleverse_xho96', 'random_bible_verse_xho96'); 

//bn
add_shortcode('bibleverseoftheday_rovu', 'bible_verse_of_the_day_rovu'); 
add_shortcode('randombibleverse_rovu', 'random_bible_verse_rovu'); 

//ur
add_shortcode('bibleverseoftheday_urd', 'bible_verse_of_the_day_urd'); 
add_shortcode('randombibleverse_urd', 'random_bible_verse_urd'); 

//hi
add_shortcode('bibleverseoftheday_hhbd', 'bible_verse_of_the_day_hhbd'); 
add_shortcode('randombibleverse_hhbd', 'random_bible_verse_hhbd'); 

//da
add_shortcode('bibleverseoftheday_bdan', 'bible_verse_of_the_day_bdan'); 
add_shortcode('randombibleverse_bdan', 'random_bible_verse_bdan'); 
add_shortcode('bibleverseoftheday_da1871', 'bible_verse_of_the_day_da1871'); 
add_shortcode('randombibleverse_da1871', 'random_bible_verse_da1871'); 

//mg
add_shortcode('bibleverseoftheday_mg1865', 'bible_verse_of_the_day_mg1865'); 
add_shortcode('randombibleverse_mg1865', 'random_bible_verse_mg1865'); 

//en
function bible_verse_of_the_day_niv() { return bible_verse_of_the_day('0', 'niv'); }
function random_bible_verse_niv() { return random_bible_verse('0', 'niv'); }
function bible_verse_of_the_day_kjv() { return bible_verse_of_the_day('0', 'kjv'); }
function random_bible_verse_kjv() { return random_bible_verse('0', 'kjv'); }
function bible_verse_of_the_day_esv() { return bible_verse_of_the_day('0', 'esv'); }
function random_bible_verse_esv() { return random_bible_verse('0', 'esv'); }
function bible_verse_of_the_day_nkjv() { return bible_verse_of_the_day('0', 'nkjv'); }
function random_bible_verse_nkjv() { return random_bible_verse('0', 'nkjv'); }
function bible_verse_of_the_day_nlt() { return bible_verse_of_the_day('0', 'nlt'); }
function random_bible_verse_nlt() { return random_bible_verse('0', 'nlt'); }
function bible_verse_of_the_day_nrsv() { return bible_verse_of_the_day('0', 'nrsv'); }
function random_bible_verse_nrsv() { return random_bible_verse('0', 'nrsv'); }
function bible_verse_of_the_day_web() { return bible_verse_of_the_day('0', 'web'); }
function random_bible_verse_web() { return random_bible_verse('0', 'web'); }
function bible_verse_of_the_day_ncb() { return bible_verse_of_the_day('0', 'ncb'); }
function random_bible_verse_ncb() { return random_bible_verse('0', 'ncb'); }

//nl
function bible_verse_of_the_day_nbv() { return bible_verse_of_the_day('0', 'nbv'); }
function random_bible_verse_nbv() { return random_bible_verse('0', 'nbv'); }
function bible_verse_of_the_day_bgt() { return bible_verse_of_the_day('0', 'bgt'); }
function random_bible_verse_bgt() { return random_bible_verse('0', 'bgt'); }
function bible_verse_of_the_day_nbg() { return bible_verse_of_the_day('0', 'nbg'); }
function random_bible_verse_nbg() { return random_bible_verse('0', 'nbg'); }
function bible_verse_of_the_day_bb() { return bible_verse_of_the_day('0', 'bb'); }
function random_bible_verse_bb() { return random_bible_verse('0', 'bb'); }
function bible_verse_of_the_day_hsv() { return bible_verse_of_the_day('0', 'hsv'); }
function random_bible_verse_hsv() { return random_bible_verse('0', 'hsv'); }

//es
function bible_verse_of_the_day_nvi() { return bible_verse_of_the_day('0', 'nvi'); }
function random_bible_verse_nvi() { return random_bible_verse('0', 'nvi'); }
function bible_verse_of_the_day_rvr95() { return bible_verse_of_the_day('0', 'rvr95'); }
function random_bible_verse_rvr95() { return random_bible_verse('0', 'rvr95'); }
function bible_verse_of_the_day_rvr60() { return bible_verse_of_the_day('0', 'rvr60'); }
function random_bible_verse_rvr60() { return random_bible_verse('0', 'rvr60'); }
function bible_verse_of_the_day_lbla() { return bible_verse_of_the_day('0', 'lbla'); }
function random_bible_verse_lbla() { return random_bible_verse('0', 'lbla'); }

//de
function bible_verse_of_the_day_lut() { return bible_verse_of_the_day('0', 'lut'); }
function random_bible_verse_lut() { return random_bible_verse('0', 'lut'); }
function bible_verse_of_the_day_neu() { return bible_verse_of_the_day('0', 'neu'); }
function random_bible_verse_neu() { return random_bible_verse('0', 'neu'); }
function bible_verse_of_the_day_elb() { return bible_verse_of_the_day('0', 'elb'); }
function random_bible_verse_elb() { return random_bible_verse('0', 'elb'); }
function bible_verse_of_the_day_lu12() { return bible_verse_of_the_day('0', 'lu12'); }
function random_bible_verse_lu12() { return random_bible_verse('0', 'lu12'); }

//pt
function bible_verse_of_the_day_arc() { return bible_verse_of_the_day('0', 'arc'); }
function random_bible_verse_arc() { return random_bible_verse('0', 'arc'); }
function bible_verse_of_the_day_nvi_pt() { return bible_verse_of_the_day('0', 'nvi-pt'); }
function random_bible_verse_nvi_pt() { return random_bible_verse('0', 'nvi-pt'); }
function bible_verse_of_the_day_ara() { return bible_verse_of_the_day('0', 'ara'); }
function random_bible_verse_ara() { return random_bible_verse('0', 'ara'); }

//sk
function bible_verse_of_the_day_kat() { return bible_verse_of_the_day('0', 'kat'); }
function random_bible_verse_kat() { return random_bible_verse('0', 'kat'); }

//it
function bible_verse_of_the_day_cei() { return bible_verse_of_the_day('0', 'cei'); }
function random_bible_verse_cei() { return random_bible_verse('0', 'cei'); }
function bible_verse_of_the_day_nr06() { return bible_verse_of_the_day('0', 'nr06'); }
function random_bible_verse_nr06() { return random_bible_verse('0', 'nr06'); }

//fr
function bible_verse_of_the_day_sg21() { return bible_verse_of_the_day('0', 'sg21'); }
function random_bible_verse_sg21() { return random_bible_verse('0', 'sg21'); }
function bible_verse_of_the_day_bds() { return bible_verse_of_the_day('0', 'bds'); }
function random_bible_verse_bds() { return random_bible_verse('0', 'bds'); }

//fi
function bible_verse_of_the_day_kr92() { return bible_verse_of_the_day('0', 'kr92'); }
function random_bible_verse_kr92() { return random_bible_verse('0', 'kr92'); }

//tc
function bible_verse_of_the_day_cuvs() { return bible_verse_of_the_day('0', 'cuvs'); }
function random_bible_verse_cuvs() { return random_bible_verse('0', 'cuvs'); }
function bible_verse_of_the_day_cuv() { return bible_verse_of_the_day('0', 'cuv'); }
function random_bible_verse_cuv() { return random_bible_verse('0', 'cuv'); }

//ar
function bible_verse_of_the_day_avd() { return bible_verse_of_the_day('0', 'avd'); }
function random_bible_verse_avd() { return random_bible_verse('0', 'avd'); }
function bible_verse_of_the_day_keh() { return bible_verse_of_the_day('0', 'keh'); }
function random_bible_verse_keh() { return random_bible_verse('0', 'keh'); }

//cs
function bible_verse_of_the_day_cep() { return bible_verse_of_the_day('0', 'cep'); }
function random_bible_verse_cep() { return random_bible_verse('0', 'cep'); }
function bible_verse_of_the_day_b21() { return bible_verse_of_the_day('0', 'b21'); }
function random_bible_verse_b21() { return random_bible_verse('0', 'b21'); }

//ru
function bible_verse_of_the_day_rst() { return bible_verse_of_the_day('0', 'rst'); }
function random_bible_verse_rst() { return random_bible_verse('0', 'rst'); }

//af
function bible_verse_of_the_day_afr53() { return bible_verse_of_the_day('0', 'afr53'); }
function random_bible_verse_afr53() { return random_bible_verse('0', 'afr53'); }

//pl
function bible_verse_of_the_day_ubg() { return bible_verse_of_the_day('0', 'ubg'); }
function random_bible_verse_ubg() { return random_bible_verse('0', 'ubg'); }

//el
function bible_verse_of_the_day_tgv() { return bible_verse_of_the_day('0', 'tgv'); }
function random_bible_verse_tgv() { return random_bible_verse('0', 'tgv'); }

//fa
function bible_verse_of_the_day_pcb() { return bible_verse_of_the_day('0', 'pcb'); }
function random_bible_verse_pcb() { return random_bible_verse('0', 'pcb'); }

//zu
function bible_verse_of_the_day_zul59() { return bible_verse_of_the_day('0', 'zul59'); }
function random_bible_verse_zul59() { return random_bible_verse('0', 'zul59'); }

//st
function bible_verse_of_the_day_sso89() { return bible_verse_of_the_day('0', 'sso89'); }
function random_bible_verse_sso89() { return random_bible_verse('0', 'sso89'); }

//xh
function bible_verse_of_the_day_xho96() { return bible_verse_of_the_day('0', 'xho96'); }
function random_bible_verse_xho96() { return random_bible_verse('0', 'xho96'); }

//bn
function bible_verse_of_the_day_rovu() { return bible_verse_of_the_day('0', 'rovu'); }
function random_bible_verse_rovu() { return random_bible_verse('0', 'rovu'); }

//ur
function bible_verse_of_the_day_urd() { return bible_verse_of_the_day('0', 'urd'); }
function random_bible_verse_urd() { return random_bible_verse('0', 'urd'); }

//hi
function bible_verse_of_the_day_hhbd() { return bible_verse_of_the_day('0', 'hhbd'); }
function random_bible_verse_hhbd() { return random_bible_verse('0', 'hhbd'); }

//da
function bible_verse_of_the_day_bdan() { return bible_verse_of_the_day('0', 'bdan'); }
function random_bible_verse_bdan() { return random_bible_verse('0', 'bdan'); }
function bible_verse_of_the_day_da1871() { return bible_verse_of_the_day('0', 'da1871'); }
function random_bible_verse_da1871() { return random_bible_verse('0', 'da1871'); }

//mg
function bible_verse_of_the_day_mg1865() { return bible_verse_of_the_day('0', 'mg1865'); }
function random_bible_verse_mg1865() { return random_bible_verse('0', 'mg1865'); }

function getLanguage() 
{
	$language = substr(get_locale(), 0, 2);
	$url = '/' . $language;
	
	if (get_language_url($language) == $url)
	{
		return $language;
	}
	
	return 'en';
}
  
class DailyVersesWidget extends WP_Widget
{
  function __construct() 
  {
	parent::__construct('DailyVersesWidget', __('Bible Verse of the Day', 'bible-verse-of-the-day'), array ('description' => __('Daily Bible verse from DailyVerses.net!', 'bible-verse-of-the-day')));
  }
    
  function form($instance)
  {
    $instance = wp_parse_args( (array) $instance, array( 'title' => __('Bible Verse of the Day', 'bible-verse-of-the-day'), 'showlink' => '0', 'language' => getLanguage() ) );
    $title = $instance['title'];
	$showlink = $instance['showlink'];
	$language = $instance['language'];
	
?>
  <p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title', 'bible-verse-of-the-day') ?>: <br /><input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" /></label></p>
  <p><?php _e('Bible translation', 'bible-verse-of-the-day') ?>: <br /><select id="<?php echo $this->get_field_id('language'); ?>" name="<?php echo $this->get_field_name('language'); ?>">
	<option value="afr53" <?php _e($language == 'afr53' ? 'selected' : ''); ?>>Afrikaans - AFR53</option>
	<option value="b21" <?php _e($language == 'b21' ? 'selected' : ''); ?>>Čeština - B21</option>
	<option value="cep" <?php _e($language == 'cep' ? 'selected' : ''); ?>>Čeština - ČEP</option>
	<option value="bdan" <?php _e($language == 'bdan' ? 'selected' : ''); ?>>Dansk - BDAN</option>
	<option value="da1871" <?php _e($language == 'da1871' ? 'selected' : ''); ?>>Dansk - DA1871</option>
	<option value="elb" <?php _e($language == 'elb' ? 'selected' : ''); ?>>Deutsch - ELB</option>
	<option value="lu12" <?php _e($language == 'lu12' ? 'selected' : ''); ?>>Deutsch - LU12</option>
	<option value="lut" <?php _e($language == 'de' || $language == 'lut' ? 'selected' : ''); ?>>Deutsch - LUT</option>
	<option value="neu" <?php _e($language == 'neu' ? 'selected' : ''); ?>>Deutsch - NeÜ</option>
	<option value="esv" <?php _e($language == 'esv' ? 'selected' : ''); ?>>English - ESV</option>
	<option value="kjv" <?php _e($language == 'kjv' ? 'selected' : ''); ?>>English - KJV</option>
	<option value="ncb" <?php _e($language == 'ncb' ? 'selected' : ''); ?>>English - NCB</option>
	<option value="niv" <?php _e($language == '' || $language == 'en' || $language == 'niv' ? 'selected' : ''); ?>>English - NIV</option>
	<option value="nkjv" <?php _e($language == 'nkjv' ? 'selected' : ''); ?>>English - NKJV</option>
	<option value="nlt" <?php _e($language == 'nlt' ? 'selected' : ''); ?>>English - NLT</option>
	<option value="nrsv" <?php _e($language == 'nrsv' ? 'selected' : ''); ?>>English - NRSV</option>
	<option value="web" <?php _e($language == 'web' ? 'selected' : ''); ?>>English - WEB</option>
	<option value="lbla" <?php _e($language == 'lbla' ? 'selected' : ''); ?>>Español - LBLA</option>
	<option value="nvi" <?php _e($language == 'es' || $language == 'nvi' ? 'selected' : ''); ?>>Español - NVI</option>
	<option value="rvr60" <?php _e($language == 'rvr60' ? 'selected' : ''); ?>>Español - RVR60</option>
	<option value="rvr95" <?php _e($language == 'rvr95' ? 'selected' : ''); ?>>Español - RVR95</option>
	<option value="bds" <?php _e($language == 'bds' ? 'selected' : ''); ?>>Français - BDS</option>
	<option value="sg21" <?php _e($language == 'fr' || $language == 'sg21' ? 'selected' : ''); ?>>Français - SG21</option>
	<option value="cei" <?php _e($language == 'it' || $language == 'cei' ? 'selected' : ''); ?>>Italiano - CEI</option>
	<option value="nr06" <?php _e($language == 'nr06' ? 'selected' : ''); ?>>Italiano - NR06</option>
	<option value="mg1865" <?php _e($language == 'mg1865' ? 'selected' : ''); ?>>Malagasy - MG1865</option>
	<option value="bb" <?php _e($language == 'bb' ? 'selected' : ''); ?>>Nederlands - BB</option>
	<option value="bgt" <?php _e($language == 'bgt' ? 'selected' : ''); ?>>Nederlands - BGT</option>
	<option value="hsv" <?php _e($language == 'hsv' ? 'selected' : ''); ?>>Nederlands - HSV</option>
	<option value="nbg" <?php _e($language == 'nbg' ? 'selected' : ''); ?>>Nederlands - NBG</option>
	<option value="nbv" <?php _e($language == 'nl' || $language == 'nbv' ? 'selected' : ''); ?>>Nederlands - NBV21</option>
	<option value="ubg" <?php _e($language == 'ubg' ? 'selected' : ''); ?>>Polski - UBG</option>
	<option value="ara" <?php _e($language == 'ara' ? 'selected' : ''); ?>>Português - ARA</option>
	<option value="arc" <?php _e($language == 'pt' || $language == 'arc' ? 'selected' : ''); ?>>Português - ARC</option>
	<option value="nvi-pt" <?php _e($language == 'nvi-pt' ? 'selected' : ''); ?>>Português - NVI</option>
	<option value="sso89" <?php _e($language == 'sso89' ? 'selected' : ''); ?>>Sesotho - SSO89</option>
	<option value="kat" <?php _e($language == 'sk' || $language == 'kat' ? 'selected' : ''); ?>>Slovenský - KAT</option>
	<option value="kr92" <?php _e($language == 'fi' || $language == 'kr92' ? 'selected' : ''); ?>>Suomi - KR92</option>
	<option value="xho96" <?php _e($language == 'xho96' ? 'selected' : ''); ?>>Xhosa - XHO96</option>
	<option value="zul59" <?php _e($language == 'zul59' ? 'selected' : ''); ?>>Zulu - ZUL59</option>
	<option value="tgv" <?php _e($language == 'tgv' ? 'selected' : ''); ?>>Ελληνικά - TGV</option>
	<option value="rst" <?php _e($language == 'rst' ? 'selected' : ''); ?>>Русский - СП</option>
	<option value="urd" <?php _e($language == 'urd' ? 'selected' : ''); ?>>اردو - URD</option>
	<option value="avd" <?php _e($language == 'avd' ? 'selected' : ''); ?>>عربى - AVD</option>
	<option value="keh" <?php _e($language == 'keh' ? 'selected' : ''); ?>>عربى - KEH</option>
	<option value="pcb" <?php _e($language == 'pcb' ? 'selected' : ''); ?>>فارسی - PCB</option>
	<option value="hhbd" <?php _e($language == 'hhbd' ? 'selected' : ''); ?>>हिन्दी - HHBD</option>
	<option value="rovu" <?php _e($language == 'rovu' ? 'selected' : ''); ?>>বাংলা - ROVU</option>
	<option value="cuv" <?php _e($language == 'cuv' ? 'selected' : ''); ?>>繁體中文 - CUV</option>
	<option value="cuvs" <?php _e($language == 'cuvs' ? 'selected' : ''); ?>>繁體中文 - CUVS</option>
  </select></p>
  <p><input id="<?php echo $this->get_field_id('showlink'); ?>" name="<?php echo $this->get_field_name('showlink'); ?>" type="checkbox" value="1" <?php checked( '1', $showlink ); ?>/><label for="<?php echo $this->get_field_id('showlink'); ?>">&nbsp;<?php _e('Show link to DailyVerses.net', 'bible-verse-of-the-day'); ?></label></p>
<?php
  }
 
  function update($new_instance, $old_instance)
  {
    $instance = $old_instance;
    $instance['title'] = $new_instance['title'];
	if(isset($new_instance['showlink']) && $new_instance['showlink'] == '1')
	{
		$instance['showlink'] = '1';
	}
	else
	{
		$instance['showlink'] = '0';
	}	
	if(!isset($new_instance['language']) || $new_instance['language'] == '')
	{
		$instance['language'] = 'niv';
	}
	else
	{
		$instance['language'] = $new_instance['language'];
	}
    return $instance;
  }
 
  function widget($args, $instance)
  {
    extract($args, EXTR_SKIP);
 
    echo $before_widget;
    $title = empty($instance['title']) ? ' ' : apply_filters('widget_title', $instance['title']);
 
    if (!empty($title))
      echo $before_title . $title . $after_title;
 
 	$showlink = $instance['showlink'];
	if($showlink == '')
	{
		$showlink = '1';
	}
	
	$language = $instance['language'];
	if($language == '')
	{
		$language = 'niv';
	}
	
    echo bible_verse_of_the_day($showlink, $language);
 
    echo $after_widget; 
  } 
}

class RandomBibleVerseWidget extends WP_Widget
{
  function __construct() 
  {
	parent::__construct('RandomBibleVerseWidget', __('Random Bible verse', 'bible-verse-of-the-day'), array ('description' => __( 'Random Bible verse from DailyVerses.net!', 'bible-verse-of-the-day')));
  }
 
  function form($instance)
  {
    $instance = wp_parse_args( (array) $instance, array( 'title' => __('Random Bible verse', 'bible-verse-of-the-day'), 'showlink' => '0', 'language' => getLanguage() ) );
    $title = $instance['title'];
	$showlink = $instance['showlink'];
	$language = $instance['language'];
	
?>
  <p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title', 'bible-verse-of-the-day') ?>: <br /><input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" /></label></p>
  <p><?php _e('Bible translation', 'bible-verse-of-the-day') ?>: <br /><select id="<?php echo $this->get_field_id('language'); ?>" name="<?php echo $this->get_field_name('language'); ?>">
	<option value="afr53" <?php _e($language == 'afr53' ? 'selected' : ''); ?>>Afrikaans - AFR53</option>
	<option value="b21" <?php _e($language == 'b21' ? 'selected' : ''); ?>>Čeština - B21</option>
	<option value="cep" <?php _e($language == 'cep' ? 'selected' : ''); ?>>Čeština - ČEP</option>
	<option value="bdan" <?php _e($language == 'bdan' ? 'selected' : ''); ?>>Dansk - BDAN</option>
	<option value="da1871" <?php _e($language == 'da1871' ? 'selected' : ''); ?>>Dansk - DA1871</option>
	<option value="elb" <?php _e($language == 'elb' ? 'selected' : ''); ?>>Deutsch - ELB</option>
	<option value="lu12" <?php _e($language == 'lu12' ? 'selected' : ''); ?>>Deutsch - LU12</option>
	<option value="lut" <?php _e($language == 'de' || $language == 'lut' ? 'selected' : ''); ?>>Deutsch - LUT</option>
	<option value="neu" <?php _e($language == 'neu' ? 'selected' : ''); ?>>Deutsch - NeÜ</option>
	<option value="esv" <?php _e($language == 'esv' ? 'selected' : ''); ?>>English - ESV</option>
	<option value="kjv" <?php _e($language == 'kjv' ? 'selected' : ''); ?>>English - KJV</option>
	<option value="ncb" <?php _e($language == 'ncb' ? 'selected' : ''); ?>>English - NCB</option>
	<option value="niv" <?php _e($language == '' || $language == 'en' || $language == 'niv' ? 'selected' : ''); ?>>English - NIV</option>
	<option value="nkjv" <?php _e($language == 'nkjv' ? 'selected' : ''); ?>>English - NKJV</option>
	<option value="nlt" <?php _e($language == 'nlt' ? 'selected' : ''); ?>>English - NLT</option>
	<option value="nrsv" <?php _e($language == 'nrsv' ? 'selected' : ''); ?>>English - NRSV</option>
	<option value="web" <?php _e($language == 'web' ? 'selected' : ''); ?>>English - WEB</option>
	<option value="lbla" <?php _e($language == 'lbla' ? 'selected' : ''); ?>>Español - LBLA</option>
	<option value="nvi" <?php _e($language == 'es' || $language == 'nvi' ? 'selected' : ''); ?>>Español - NVI</option>
	<option value="rvr60" <?php _e($language == 'rvr60' ? 'selected' : ''); ?>>Español - RVR60</option>
	<option value="rvr95" <?php _e($language == 'rvr95' ? 'selected' : ''); ?>>Español - RVR95</option>
	<option value="bds" <?php _e($language == 'bds' ? 'selected' : ''); ?>>Français - BDS</option>
	<option value="sg21" <?php _e($language == 'fr' || $language == 'sg21' ? 'selected' : ''); ?>>Français - SG21</option>
	<option value="cei" <?php _e($language == 'it' || $language == 'cei' ? 'selected' : ''); ?>>Italiano - CEI</option>
	<option value="nr06" <?php _e($language == 'nr06' ? 'selected' : ''); ?>>Italiano - NR06</option>
	<option value="mg1865" <?php _e($language == 'mg1865' ? 'selected' : ''); ?>>Malagasy - MG1865</option>
	<option value="bb" <?php _e($language == 'bb' ? 'selected' : ''); ?>>Nederlands - BB</option>
	<option value="bgt" <?php _e($language == 'bgt' ? 'selected' : ''); ?>>Nederlands - BGT</option>
	<option value="hsv" <?php _e($language == 'hsv' ? 'selected' : ''); ?>>Nederlands - HSV</option>
	<option value="nbg" <?php _e($language == 'nbg' ? 'selected' : ''); ?>>Nederlands - NBG</option>
	<option value="nbv" <?php _e($language == 'nl' || $language == 'nbv' ? 'selected' : ''); ?>>Nederlands - NBV21</option>
	<option value="ubg" <?php _e($language == 'ubg' ? 'selected' : ''); ?>>Polski - UBG</option>
	<option value="ara" <?php _e($language == 'ara' ? 'selected' : ''); ?>>Português - ARA</option>
	<option value="arc" <?php _e($language == 'pt' || $language == 'arc' ? 'selected' : ''); ?>>Português - ARC</option>
	<option value="nvi-pt" <?php _e($language == 'nvi-pt' ? 'selected' : ''); ?>>Português - NVI</option>
	<option value="sso89" <?php _e($language == 'sso89' ? 'selected' : ''); ?>>Sesotho - SSO89</option>
	<option value="kat" <?php _e($language == 'sk' || $language == 'kat' ? 'selected' : ''); ?>>Slovenský - KAT</option>
	<option value="kr92" <?php _e($language == 'fi' || $language == 'kr92' ? 'selected' : ''); ?>>Suomi - KR92</option>
	<option value="xho96" <?php _e($language == 'xho96' ? 'selected' : ''); ?>>Xhosa - XHO96</option>
	<option value="zul59" <?php _e($language == 'zul59' ? 'selected' : ''); ?>>Zulu - ZUL59</option>
	<option value="tgv" <?php _e($language == 'tgv' ? 'selected' : ''); ?>>Ελληνικά - TGV</option>
	<option value="rst" <?php _e($language == 'rst' ? 'selected' : ''); ?>>Русский - СП</option>
	<option value="urd" <?php _e($language == 'urd' ? 'selected' : ''); ?>>اردو - URD</option>
	<option value="avd" <?php _e($language == 'avd' ? 'selected' : ''); ?>>عربى - AVD</option>
	<option value="keh" <?php _e($language == 'keh' ? 'selected' : ''); ?>>عربى - KEH</option>
	<option value="pcb" <?php _e($language == 'pcb' ? 'selected' : ''); ?>>فارسی - PCB</option>
	<option value="hhbd" <?php _e($language == 'hhbd' ? 'selected' : ''); ?>>हिन्दी - HHBD</option>
	<option value="rovu" <?php _e($language == 'rovu' ? 'selected' : ''); ?>>বাংলা - ROVU</option>
	<option value="cuv" <?php _e($language == 'cuv' ? 'selected' : ''); ?>>繁體中文 - CUV</option>
	<option value="cuvs" <?php _e($language == 'cuvs' ? 'selected' : ''); ?>>繁體中文 - CUVS</option>
  </select></p>
  <p><input id="<?php echo $this->get_field_id('showlink'); ?>" name="<?php echo $this->get_field_name('showlink'); ?>" type="checkbox" value="1" <?php checked( '1', $showlink ); ?>/><label for="<?php echo $this->get_field_id('showlink'); ?>">&nbsp;<?php _e('Show link to DailyVerses.net', 'bible-verse-of-the-day'); ?></label></p>
<?php
  }
 
  function update($new_instance, $old_instance)
  {
    $instance = $old_instance;
    $instance['title'] = $new_instance['title'];
	if(isset($new_instance['showlink']) && $new_instance['showlink'] == '1')
	{
		$instance['showlink'] = '1';
	}
	else
	{
		$instance['showlink'] = '0';
	}
	if(!isset($new_instance['language']) || $new_instance['language'] == '')
	{
		$instance['language'] = 'niv';
	}
	else
	{
		$instance['language'] = $new_instance['language'];
	}
    return $instance;
  }
 
  function widget($args, $instance)
  {
    extract($args, EXTR_SKIP);
 
    echo $before_widget;
    $title = empty($instance['title']) ? ' ' : apply_filters('widget_title', $instance['title']);
 
    if (!empty($title))
      echo $before_title . $title . $after_title;
 
 	$showlink = $instance['showlink'];
	if($showlink == '')
	{
		$showlink = '1';
	}
	
	$language = $instance['language'];
	if($language == '')
	{
		$language = 'niv';
	}
	
    echo random_bible_verse($showlink, $language);
 
    echo $after_widget;
  }
}

function register_DailyVersesWidget() { 
  register_widget('DailyVersesWidget');
}


function register_RandomBibleVerseWidget() { 
  register_widget('RandomBibleVerseWidget');
}

add_action( 'widgets_init', 'register_DailyVersesWidget');
add_action( 'widgets_init', 'register_RandomBibleVerseWidget');
?>