var objPostsEditorView;
var g_isPro = sheetspilot.editor.g_isPro;
var g_showdebug = sheetspilot.editor.g_showdebug;
var g_showtrace = sheetspilot.editor.g_showtrace;

var g_urlAjaxActionsSheetsPilot = sheetspilot.editor.g_urlAjaxActionsSheetsPilot;
var g_paginationPostsPerPage = sheetspilot.editor.g_paginationPostsPerPage;
var g_baseURL = sheetspilot.editor.g_baseURL;
var g_urlImagePlaceholder = sheetspilot.editor.g_urlImagePlaceholder;

var g_isContextOff = sheetspilot.editor.g_isContextOff;
var g_postEditLink = sheetspilot.editor.g_postEditLink;
var g_postElementorEditLink = sheetspilot.editor.g_postElementorEditLink || '';
var g_pluginTitle = sheetspilot.editor.g_pluginTitle;

var g_doublyNonce = jQuery('#g_doublyNonce').val();
var g_latestPromptsText = jQuery('#g_latestPromptsText').val();
var g_savedPromptsText = jQuery('#g_savedPromptsText').val();
var g_ubaiPromptsStrings = jQuery('#g_ubaiPromptsStrings').val();
var g_ubaiPromptsIcons = jQuery('#g_ubaiPromptsIcons').val();
var g_ubaiCellRules = jQuery('#g_ubaiCellRules').val();
if (typeof g_ubaiCellRules === 'string' && g_ubaiCellRules.length) {
	try {
		g_ubaiCellRules = JSON.parse(g_ubaiCellRules);
	} catch (e) {
		g_ubaiCellRules = {};
	}
}
if (typeof g_ubaiCellRules !== 'object' || g_ubaiCellRules === null) {
	g_ubaiCellRules = {};
}

var spreadsheet;
var g_tableStructure;

// copy past global
var g_copyPastValue = '';
var g_copyPastHTML = '';
var g_copyPastValueArray = [];
var g_copyPastValueTextArray = [];
var g_copyPastValueImgArray = [];

var g_copyPastType = '';
var g_copyPastImageURL = '';
var g_copyPastSelectName = '';
var g_copyPastWYSWYGSourceID = false;

// wysiwyg
var g_wyswygEditorID = false;
var g_wyswygPostID = false;
var g_wyswygFieldName = false;
var g_wyswygCellID = false;

// ajax processingflaf
var g_ajaxRunningFlag = false;