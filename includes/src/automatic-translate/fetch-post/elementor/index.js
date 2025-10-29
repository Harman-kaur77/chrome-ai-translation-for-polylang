import { dispatch } from "@wordpress/data";
import AllowedMetaFields from "../../allowed-meta-fields";
import ElementorSaveSource from "../../store-source-string/elementor";

// Update allowed meta fields
const updateAllowedMetaFields = (data) => {
    dispatch('block-catfp/translate').allowedMetaFields(data);
}

const fetchPostContent = async (props) => {
    const elementorPostData = catfp_global_object.elementorData && typeof catfp_global_object.elementorData === 'string' ? JSON.parse(catfp_global_object.elementorData) : catfp_global_object.elementorData;

    const content={
        widgetsContent:elementorPostData,
        metaFields:catfp_global_object?.metaFields || {}
    }

    if(catfp_global_object.parent_post_title && '' !== catfp_global_object.parent_post_title){
        content.title=catfp_global_object.parent_post_title;
    }

    // Update allowed meta fields
    Object.keys(AllowedMetaFields).forEach(key => {
        updateAllowedMetaFields({id: key, type: AllowedMetaFields[key].type});
    });
    
    ElementorSaveSource(content);
    
    props.refPostData(content);
    props.updatePostDataFetch(true);
}

export default fetchPostContent;