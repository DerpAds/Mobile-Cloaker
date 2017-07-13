<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8" />
    <meta name='viewport' content='width=device-width, initial-scale=1' />
    <title>NWWF</title>
	<?php echo $head_placeholder?>
</head>

<body{onload}>
	<?php foreach ($contents as $content):?>
		<div id="<?php $content->id?>">
			<?php $this->load->view($content->view, $content->data);?>
		</div>
	<?php endforeach;?>
</body>
</html>