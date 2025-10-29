const ClassicWidgetTranslator = (props) => {
  const value = props.getContent();

  const activePageLanguage = window.catfpInlineTranslation?.pageLanguage || 'en';
  const TranslatorModal = window?.catfpInlineTranslation?.TranslatorModal;

  if (!TranslatorModal) {
    return <div>TranslatorModal not found</div>;
  }
  
  const onUpdateHandler = (value) => {
    props.setContent(value);
    props.unMountToolTip();
  }

  const onCloseHandler = () => {
    props.onCloseHandler();
  }

  return <TranslatorModal modalOpen={true} value={value} onUpdate={onUpdateHandler} pageLanguage={activePageLanguage} onModalClose={onCloseHandler} />
}

export default ClassicWidgetTranslator;