<style>
    .paging a {
        border: 1px solid #4e73df;
        padding: 5px 13px;
        list-style: none;
        background: #fff;
        margin: 0 4px;
        border-radius: 4px;
        color: #4e73df;
        text-decoration: none;
    }
</style>

<div class="paging d-flex justify-content-center">
    <?php
    for ($i = 1; $i <= $page; $i++) {
    ?>
        <a <?php if ($i == $currentPage) echo 'style="background-color: #4e73df; border-color: #4e73df; color:white;"' ?> href="xulysanpham.php?page=<?php echo $i ?>"><?php echo $i ?></a>
    <?php
    }
    ?>

</div>