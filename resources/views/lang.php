<?php echo '<?php'; ?>

return <?php echo View::make('laravel-lang-tools::array')->with(array('items'=>$items,'indentWith'=>$indentWith))->render(); ?>;
