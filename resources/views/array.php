<?php
if ( ! isset($level) )
{
	$level = 0;
}
$indentStr = str_repeat($indentWith, $level)
?>
<?php echo $indentStr; ?>array(
<?php foreach( $items as $key => $item ): ?>
<?php
    echo $indentStr.$indentWith.'"'.e($key).'" => ';
    echo (is_array($item)) ? View::make('laravel-lang-tools::array', array('level' => $level + 1, 'items' => $item,'indentWith'=>$indentWith))->render() : '"'.str_replace('""','\"',$item).'"';
?>,
<?php endforeach ?>
<?php echo $indentStr; ?>)