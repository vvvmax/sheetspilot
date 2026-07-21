class SheetPilotSmallModal{
	constructor( modal_data ) {
		this.mainContainerID = '#ubai_context_menus_content_popup';
		this.g_classActive = "ue-active";
		this.g_modalHeaderSelector = ".unlimitedai-plugin__popup-header";
		this.g_modalTitleSelector = ".unlimitedai-plugin__popup-header__title";
		this.g_modalTitleSubSelector = ".unlimitedai-plugin__popup-header__subtitle";
		this.g_modalInputSelector = ".unlimitedai-plugin__popup-input";
		this.g_modalSubmitSelector = ".unlimitedai-plugin__popup__button.add";
		this.g_modalResetSelector = ".unlimitedai-plugin__popup__button.reset";
		this.g_modalBodySelector = ".unlimitedai-plugin__popup-body";

		this.g_modalBodyNumberSelector = ".unlimitedai-plugin__popup__number";
		this.g_modalBodyButtonsSelector = ".unlimitedai-plugin__popup__buttons";

		// init objects
		this.objSmallModal = jQuery( this.mainContainerID );


		// run functionality
		this.setTitle( modal_data.title );
		this.setSubtitle( modal_data.subtitle );
		this.setInputPlaceholder( modal_data.placeholder );
		this.setSubmitText( modal_data.button_text );
		this.setSubmitId( modal_data.button_id );
		this.setInputId( modal_data.input_id );
		this.dropInputvalue( );
		this.setInputType( modal_data.input_type );
		this.setResetId( modal_data.reset_id );
		

		this.initEvents();
	}
	 
	initEvents() {

	}

	showModal(){
		this.objSmallModal.addClass(this.g_classActive)
	}

	hideModal(){
		this.objSmallModal.removeClass(this.g_classActive)
	}

	setTitle( title ){
		this.objSmallModal.find( this.g_modalTitleSelector ).html( title );
	}

	setSubtitle( subtitle ){
		this.objSmallModal.find( this.g_modalTitleSubSelector ).html( subtitle );
	}

	setInputPlaceholder( placeholder ){
		this.objSmallModal.find( this.g_modalInputSelector ).attr( 'placeholder', placeholder );
	}
	setSubmitText( submit_text ){
		this.objSmallModal.find( this.g_modalSubmitSelector ).html( submit_text );
	}
	setSubmitId( submit_id ){
		this.objSmallModal.find( this.g_modalSubmitSelector ).attr( 'id', submit_id );
	}
	setInputId( input_id ){
		this.objSmallModal.find( this.g_modalInputSelector ).attr( 'id', input_id );
	}
	setInputType( input_type ){
		this.objSmallModal.find( this.g_modalInputSelector ).attr( 'type', input_type );
	}
	setInputvalue( input_value ){
		this.objSmallModal.find( this.g_modalInputSelector ).val( input_value );
	}
	dropInputvalue( ){
		this.objSmallModal.find( this.g_modalInputSelector ).val('');
	}

	showReset(){
		this.objSmallModal.find( this.g_modalResetSelector ).show();
	}
	hideReset(){
		this.objSmallModal.find( this.g_modalResetSelector ).hide();
	}
	setResetId( reset_id ){
		this.objSmallModal.find( this.g_modalResetSelector ).attr( 'id', reset_id );
	}
	setModalContent( content ){
		this.objSmallModal.find( this.g_modalHeaderSelector ).hide();
		this.objSmallModal.find( this.g_modalBodyNumberSelector ).hide();
		this.objSmallModal.find( this.g_modalBodyButtonsSelector ).hide();
		this.objSmallModal.find( this.g_modalBodySelector ).html( content );
	}
	hideTitle( ){
		this.objSmallModal.find( this.g_modalTitleSelector ).hide();
	}
	showTitle( ){
		this.objSmallModal.find( this.g_modalTitleSelector ).show();
	}
}
