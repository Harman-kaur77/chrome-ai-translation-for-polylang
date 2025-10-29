import createBlocks from './create-block';
import { dispatch, select } from '@wordpress/data';
import YoastSeoFields from '../../component/translate-seo-fields/yoast-seo-fields';
import RankMathSeo from '../../component/translate-seo-fields/rank-math-seo';
import SeoPressFields from '../../component/translate-seo-fields/seo-press';

/**
 * Translates the post content and updates the post title, excerpt, and content.
 * 
 * @param {Object} props - The properties containing post content, translation function, and block rules.
 */
const translatePost = (props) => {
    const { editPost } = dispatch('core/editor');
    const { modalClose, postContent, service } = props;

    /**
     * Updates the post title and excerpt text based on translation.
     */
    const postDataUpdate = () => {
        const data = {};
        const editPostData = Object.keys(postContent).filter(key => ['title', 'excerpt'].includes(key));

        editPostData.forEach(key => {
            const sourceData = postContent[key];
            if (sourceData.trim() !== '') {
                const translateContent = select('block-catfp/translate').getTranslatedString(key, sourceData, null, service);

                data[key] = translateContent;
            }
        });

        editPost(data);
    }

    /**
     * Updates the post meta fields based on translation.
     */
    const postMetaFieldsUpdate = () => {
        const metaFieldsData = postContent.metaFields;
        const AllowedMetaFields = select('block-catfp/translate').getAllowedMetaFields();
        
        if(!metaFieldsData || Object.keys(metaFieldsData).length < 1){
            return;
        }

        if (window.acf) {
            acf.getFields().forEach(field => {
                const fieldData=JSON.parse(JSON.stringify({key: field.data.key, type: field.data.type, name: field.data.name}));
                let repeaterField = false;
                // Update repeater fields
                if(field.$el && field.$el.closest('.acf-field.acf-field-repeater') && field.$el.closest('.acf-field.acf-field-repeater').length > 0){
                    const rowId=field.$el.closest('.acf-row').data('id');
                    const repeaterItemName=field.$el.closest('.acf-field.acf-field-repeater').data('name');

                    if(rowId && '' !== rowId){
                        const index=rowId.replace('row-', '');
                    
                        fieldData.name=repeaterItemName+'_'+index+'_'+fieldData.name;
                        repeaterField = true;
                    }

                }

               if(field.data && field.data.key && Object.keys(AllowedMetaFields).includes(fieldData.name)){
                   const fieldName = field.data.name;
                   const inputType = field.data.type;

                   const sourceValue = metaFieldsData[fieldName] && metaFieldsData[fieldName][0] ? metaFieldsData[fieldName][0] : field?.val();

                    const translatedMetaFields = select('block-catfp/translate').getTranslatedString('metaFields', sourceValue, fieldData.name, service);

                    if(!translatedMetaFields || '' === translatedMetaFields){
                        return;
                    }

                    if('wysiwyg' === inputType && window.tinymce){
                        const editorId = field.data.id;
                        tinymce.get(editorId)?.setContent(translatedMetaFields);
                    }else{
                        field.val(translatedMetaFields);
                    }
               }
            });
        }
    }

    /**
     * Updates the post ACF fields based on translation.
     */
    const postAcfFieldsUpdate = () => {
        const AllowedMetaFields = select('block-catfp/translate').getAllowedMetaFields();
        const metaFieldsData = postContent.metaFields;

        if(!metaFieldsData || Object.keys(metaFieldsData).length < 1){
            return;
        }

        if (window.acf) {
            acf.getFields().forEach(field => {

                const fieldData=JSON.parse(JSON.stringify({key: field.data.key, type: field.data.type, name: field.data.name}));
                let repeaterField = false;
                // Update repeater fields
                if(field.$el && field.$el.closest('.acf-field.acf-field-repeater') && field.$el.closest('.acf-field.acf-field-repeater').length > 0){
                    const rowId=field.$el.closest('.acf-row').data('id');
                    const repeaterItemName=field.$el.closest('.acf-field.acf-field-repeater').data('name');

                    if(rowId && '' !== rowId){
                        const index=rowId.replace('row-', '');
                    
                        fieldData.name=repeaterItemName+'_'+index+'_'+fieldData.name;
                        repeaterField = true;
                    }

                }

               if(field.data && field.data.key && Object.keys(AllowedMetaFields).includes(fieldData.name)){
                   const fieldName = field.data.name;
                   const inputType = field.data.type;

                   const sourceValue = metaFieldsData[fieldName] && metaFieldsData[fieldName][0] ? metaFieldsData[fieldName][0] : field?.val();

                   const translatedMetaFields = select('block-catfp/translate').getTranslatedString('metaFields', sourceValue, fieldData.name, service);

                   if(!translatedMetaFields || '' === translatedMetaFields){
                       return;
                   }

                   if('wysiwyg' === inputType && window.tinymce){
                       const editorId = field.data.id;
                       tinymce.get(editorId)?.setContent(translatedMetaFields);
                   }else{
                       field.val(translatedMetaFields);
                   }
               }
            });
        }
    }

    /**
     * Updates the post content based on translation.
     */
    const postContentUpdate = () => {
        const postContentData = postContent.content;

        if (postContentData.length <= 0) {
            return;
        }

        Object.values(postContentData).forEach(block => {
            createBlocks(block, service);
        });
    }

    // Update post title and excerpt text
    postDataUpdate();
    // Update post meta fields
    postMetaFieldsUpdate();
    // Update post ACF fields
    postAcfFieldsUpdate();
    // Update post content
    postContentUpdate();
    // Close string modal box
    modalClose();
}

export default translatePost;