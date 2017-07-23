<div>
    <?php foreach($orders as $o):?>
        <div class="row">
            <div class="cell">
                <?= $o->code;?>
            </div>
            <div class="cell">
                <?php echo isset($trackings[$o->code]) ? $trackings[$o->code]['tag'].'('.$trackings[$o->code]['slug'].')': '';?>
            </div>
        </div>
    <?php endforeach;?>
</div>

<style>
    .cell {
        float:left;
        margin-right: 20px;
    }
</style>