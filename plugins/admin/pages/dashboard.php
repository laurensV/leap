<h3>Dashboard</h3>
<div id="dashboard" class="row">
<?php
	if(isset($links)){
		foreach($links as $link){
			switch (strtolower($link['link'])) {
				case 'admin/dashboard':
					$icon = '<span class="glyphicon glyphicon-dashboard" aria-hidden="true"></span>';
					break;
				case 'admin/plugins':
					$icon = '<span class="glyphicon glyphicon-list-alt" aria-hidden="true"></span>';
					break;
				default:
					$icon = '<span class="glyphicon glyphicon-menu-right" aria-hidden="true"></span>';
					break;
			}
			echo '<div class="col-xs-12 col-sm-6 col-md-4 col-lg-3"><div class="block">'.$icon.'<h4>'. l($link['name'], $link['link'], array("title"=>$link['description'])) . '</h4></div></div>';
		}
	}
?>
</div>


