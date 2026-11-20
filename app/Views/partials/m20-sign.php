<div class="m20-sign">
    <div>
        <div class="cap">የጠየቀውና የወሰደው ሰው ስምና ፊርማ</div>
        <div class="m20-sline">
            <span class="lbl">ስም</span>
            <span class="v"><?= $e(mb_convert_case($_SESSION['full_name'] ?? '', MB_CASE_TITLE, "UTF-8")) ?></span>
        </div>
        <div class="m20-sline">
            <span class="lbl">ፊርማ</span>
            <span class="v"></span>
        </div>
    </div>

    <div>
        <div class="cap">ያሻሻለው ባለሥልጣን</div>
        <div class="m20-sline">
            <span class="lbl">ስም</span>
            <span class="v"></span>
        </div>
        <div class="m20-sline">
            <span class="lbl">ፊርማ</span>
            <span class="v"></span>
        </div>
    </div>
</div>