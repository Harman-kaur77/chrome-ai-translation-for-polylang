import ControlBase from './control-base';

const App = () => {
    const prefix = 'catfpElementorInlineTranslation';
    return new ControlBase(prefix);
}

jQuery(window).on('elementor:loaded', function () {
    App();
})