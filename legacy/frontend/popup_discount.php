<!-- popup_discount.php -->
<style>
#discount-popup {
    position: fixed;
    top: 0; left: 0; right:0; bottom:0;
    background-color: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}
#discount-popup .popup-content {
    background: #fff;
    padding: 30px;
    border-radius: 10px;
    text-align: center;
    position: relative;
    max-width: 400px;
}
#discount-popup .close-btn {
    position: absolute;
    top: 10px;
    right: 15px;
    cursor: pointer;
    font-size: 18px;
    font-weight: bold;
}
</style>

<div id="discount-popup">
    <div class="popup-content">
        <span class="close-btn" onclick="document.getElementById('discount-popup').style.display='none'">X</span>
        <h3>🎉 Chương trình khuyến mãi đặc biệt!</h3>
        <p>Mua trên 3.000.000 VNĐ: Giảm 10%</p>
        <p>Mua trên 5.000.000 VNĐ: Giảm 20%</p>
        <p>Mua trên 10.000.000 VNĐ: Giảm 30%</p>
    </div>
</div>
