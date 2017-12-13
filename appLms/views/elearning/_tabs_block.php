<div class="yui-t6">
    <div class="yui-b">
        <?php
//        require_once (_lms_.'/admin/controllers/CourseAlmsController.php');
//        $alms = new CourseAlmsController();
//        $params = $alms->categoryTreeParams();
//        print_r($params);
        $languages = array(
            '_ROOT' => $root_name,
            '_NEW_FOLDER_NAME' => Lang::t('_NEW_CATEGORY', 'course'),
            '_MOD' => Lang::t('_MOD', 'course'),
            '_AREYOUSURE' => Lang::t('_AREYOUSURE', 'standard'),
            '_NAME' => Lang::t('_NAME', 'standardt'),
            '_MOD' => Lang::t('_MOD', 'standard'),
            '_DEL' => Lang::t('_DEL', 'standard'),
            '_MOVE' => Lang::t('_MOVE', 'standard'),
            '_SAVE' => Lang::t('_SAVE', 'standard'),
            '_CONFIRM' => Lang::t('_CONFIRM', 'standard'),
            '_UNDO' => Lang::t('_UNDO', 'standard'),
            '_ADD' => Lang::t('_ADD', 'standard'),
            '_YES' => Lang::t('_YES', 'standard'),
            '_NO' => Lang::t('_NO', 'standard'),
            '_INHERIT' => Lang::t('_ORG_CHART_INHERIT', 'organization_chart'),
            '_NEW_FOLDER' => Lang::t('_NEW_FOLDER', 'organization_chart'),
            '_DEL' => Lang::t('_DEL', 'standard'),
            '_AJAX_FAILURE' => Lang::t('_CONNECTION_ERROR', 'standard')
        );
        $_tree_params = array(
            'id' => 'category_tree',
            'ajaxUrl' => 'ajax.adm_server.php?r=' . $base_link_course . '/gettreedata',
            'treeClass' => 'CourseFolderTree',
            'treeFile' => Get::rel_path('lms') . '/views/elearning/coursefoldertree.js',
            'languages' => $languages,
            'initialSelectedNode' => $initial_selected_node,
            'dragDrop' => false,
            'className' => tesst
        );
        $this->widget('tree', $_tree_params);
        ?>
    </div>
    <div id="yui-main">
        <div class="yui-b">

            <div class="middlearea_container">
                <?php
                $w = $this->widget('lms_tab', array(
                    'active' => 'elearning',
                    'close' => false
                ));

                // draw search
                $_model = new ElearningLms();
                $_auxiliary = Form::getInputDropdown('', 'course_search_filter_year', 'filter_year', $_model->getFilterYears(Docebo::user()->getIdst()), 0, '');

                $this->widget('tablefilter', array(
                    'id' => 'course_search',
                    'filter_text' => "",
                    'auxiliary_filter' => Lang::t('_SEARCH', 'standard') . ":&nbsp;&nbsp;&nbsp;" . $_auxiliary,
                    'js_callback_set' => 'course_search_callback_set',
                    'js_callback_reset' => 'course_search_callback_reset',
                    'css_class' => 'tabs_filter'
                ));

                $w->endWidget();
                ?>
            </div>
        </div>
    </div>

    <div class="nofloat"></div>
</div>
<script type="text/javascript">
    var lb = new LightBox();
    lb.back_url = 'index.php?r=elearning/show&sop=unregistercourse';
    var tabView = new YAHOO.widget.TabView();


    var mytab = new YAHOO.widget.Tab({
        label: '<?php echo Lang::t('_ALL_OPEN', 'course'); ?>',
        dataSrc: 'ajax.server.php?r=elearning/all&rnd=<?php echo time(); ?>',
        cacheData: true,
        loadMethod: "POST"
    });
    mytab.addListener('contentChange', function () {
        lb.init();
        this.set("cacheData", true);
    });
    tabView.addTab(mytab, 0);

<?php if ($this->isTabActive('new')): ?>
        mytab = new YAHOO.widget.Tab({
            label: '<?php echo Lang::t('_NEW', 'course'); ?>',
            dataSrc: 'ajax.server.php?r=elearning/new&rnd=<?php echo time(); ?>',
            cacheData: true,
            loadMethod: "POST"
        });
        mytab.addListener('contentChange', function () {
            lb.init();
            this.set("cacheData", true);
        });
        tabView.addTab(mytab, 1);
<?php endif; ?>

<?php if ($this->isTabActive('inprogress')): ?>
        mytab = new YAHOO.widget.Tab({
            label: '<?php echo Lang::t('_USER_STATUS_BEGIN', 'course'); ?>',
            dataSrc: 'ajax.server.php?r=elearning/inprogress&rnd=<?php echo time(); ?>',
            cacheData: true,
            loadMethod: "POST"
        });
        mytab.addListener('contentChange', function () {
            lb.init();
            this.set("cacheData", true);
        });
        tabView.addTab(mytab, 2);
<?php endif; ?>

<?php if ($this->isTabActive('completed')): ?>
        mytab = new YAHOO.widget.Tab({
            label: '<?php echo Lang::t('_COMPLETED', 'course'); ?>',
            dataSrc: 'ajax.server.php?r=elearning/completed&rnd=<?php echo time(); ?>',
            cacheData: true,
            loadMethod: "POST"
        });
        mytab.addListener('contentChange', function () {
            lb.init();
            this.set("cacheData", true);
        });
        tabView.addTab(mytab, 3);
<?php endif; ?>

<?php if ($this->isTabActive('suggested') && false): ?>
        mytab = new YAHOO.widget.Tab({
            label: '<?php echo Lang::t('_SUGGESTED', 'course'); ?>',
            dataSrc: 'ajax.server.php?r=elearning/suggested&rnd=<?php echo time(); ?>',
            cacheData: true,
            loadMethod: "POST"
        });
        mytab.addListener('contentChange', function () {
            lb.init();
            this.set("cacheData", true);
        });
        tabView.addTab(mytab, 4);
<?php endif; ?>

    tabView.appendTo('tab_content');
    tabView.getTab(0).addClass('first');
    tabView.set('activeIndex', 0);
    
    YAHOO.util.Event.onDOMReady(function () {
        var classroom = YAHOO.util.Dom.get('classroom');
        var descendants = YAHOO.util.Dom.get('descendants');
        var waiting = YAHOO.util.Dom.get('waiting');
        var button_sub = YAHOO.util.Dom.get('c_filter_set');
        var button_res = YAHOO.util.Dom.get('c_filter_reset');
        var form = YAHOO.util.Dom.get('course_filters');
        var category = YAHOO.util.Dom.get('category_tree_0');
        
        YAHOO.util.Event.addListener(classroom, 'change', filterEvent);
        YAHOO.util.Event.addListener(descendants, 'change', filterEvent);
        YAHOO.util.Event.addListener(waiting, 'change', filterEvent);
        YAHOO.util.Event.addListener(button_sub, 'click', filterEvent);
        YAHOO.util.Event.addListener(button_res, 'click', resetEvent);
        YAHOO.util.Event.addListener(form, 'submit', filterEvent);
        YAHOO.util.Event.addListener(category, 'click', displayChild);
    });
    
    function displayChild(){
        document.getElementById('ygtvc1').setAttributes("style", "display:block");
    }
    
</script>