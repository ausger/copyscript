<?php 
if($session_id){ ?>
<form action="index.php" method="post">
<input name="action" type="hidden" value="disconnect" />
<input name="Disconnect" type="submit" value="Disconnect"  />
</form>
<?php  } ?>