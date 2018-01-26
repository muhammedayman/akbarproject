<div id="progressBar">
<ul>
<?php $this->model->resetSteps(); $current =& $this->model->getCurrentStep(); $progress_first_step = 0; ?>

<?php while($item =& $this->model->getStep()) { ?>
  <li id="<?php echo ($item->get('name') == $current->get('name')) ? 'current' : 'waiting' ?>">

    <span><?php echo ($progress_first_step == 0) ? "<a href='?mdl=".$this->get('target')."'>" : ""?><?php echo $item->get('title')?><?php echo ($progress_first_step == 0) ? "</a>" : ""?></span>
    </li>
<?php $progress_first_step++; } ?>
</ul>
</div>