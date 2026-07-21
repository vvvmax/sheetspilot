

// copy past global END
var columnsListGlobal;
var g_cellProcessingObj = new SheetsPilot_CellProcessing('#new_output_table');
g_cellProcessingObj.initEvents();

var g_drawer = new SheetsPilot_Drawer();
g_drawer.initEvents();

var g_notification = new SheetsPilot_Notification();
g_notification.initEvents();

// init drawer
g_cellProcessingObj.editorDrawer = g_drawer;

var g_topFilteringBar = new SheetsPilot_TopFilteringBar();
g_topFilteringBar.initEvents();

var g_prodTypeVariable = new SheetsPilot_VariableProducts();
g_prodTypeVariable.initEvents();

// attributes editor
var spAttributesEditor = new SheetsPilot_AttributesEditor();
spAttributesEditor.initEvents();

// repeater editor
var g_Repeater = new SheetsPilotRepeaterEditor();
g_Repeater.initEvents();


jQuery(document).ready(function () {

	objPostsEditorView = new SheetsPilot_PostsEditorView('sheetspilot_postseditor', "save_edited_posts");
	objPostsEditorView.init();
	objPostsEditorView.initEvents();
	objPostsEditorView.runInitialTableContentLoad();
});