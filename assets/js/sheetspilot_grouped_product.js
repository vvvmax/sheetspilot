class SheetsPilot_GroupedProducts {

	constructor() {

		// classes
		this.g_doublyAdmin = new UniteAdminSheetsPilot();
		this.g_postEditorView = objPostsEditorView;
		this.g_cellProcessingObj = g_cellProcessingObj;


		// selectors
		
	}

	initEvents() {
		var self = this;

		this.g_objDrawerCloseBtn.on("click", function () {
			self.onDrawerClose()
		});
		this.g_objDrawerOverlay.on("click", function () {
			self.onDrawerClose()
		});

		// inline edit featured image
		this.g_objPostsEditor.on("click", this.g_inlineEditFeaturedImage, (e) => {
			this.inlineModifyFeaturedImage(e);
		});

		// inline edit stock editor
		this.g_objPostsEditor.on("click", this.openWooManageStock, (e) => {
			this.openWooManageStockFn(e);
		});

		// inline edit downloadable
		this.g_objPostsEditor.on("click", this.openWooEditDownloadable, (e) => {
			this.openWooEditDownloadableFn(e);
		});

		// inline edit downloadable
		this.objPluginSideDrawer.on("click", '.' + this.g_sideDraweAddFileButton, (e) => {
			this.addSingleFileUploadFiled(e);
		});

		// remove single file in drawer
		this.objPluginSideDrawer.on("click", '.' + this.drawerSingleFileBlockRemove, (e) => {
			this.removeSingleuploadFileBlock(e);
		});

			// acf_select related actions on click
		this.g_objPostsEditor.on("click", this.g_incellRelationEditor+'_external', (e) => {
			this.openPostTypeRelationsEditor(e);
		});	

	}

}