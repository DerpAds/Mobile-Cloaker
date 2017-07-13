<?php if (!is_array($value)):?>
    <?php echo $value;?>
<?php elseif (empty($value)):?>
    0
<?php else:?>
    <?php foreach ($value as $key => $values):?>
    {
        <strong><?php echo $key;?></strong><br/>
        <table width="100%" border="1">
            <?php foreach ($values as $param => $paramValue):?>
                <tr><td width="50%"><?php echo $param?></td><td><?php echo $paramValue?></td></tr>
            <?php endforeach;?>
        </table>
    <?php endforeach;?>

<?php endif;?>
