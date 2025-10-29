import { select, dispatch } from "@wordpress/data";
import { __ } from "@wordpress/i18n";
import ClassicSaveSource from "../../store-source-string/classic";
import AllowedMetaFields from "../../allowed-meta-fields";

const ClassicPostFetch = async (props) => {
    const apiUrl = catfp_global_object.ajax_url;
    const apiController = [];

    const destroyHandler = () => {
        apiController.forEach(controller => {
            controller.abort('Modal Closed');
        });
    }

    props.updateDestroyHandler(() => {
        destroyHandler();
    });

    // Update allowed meta fields
    const updateAllowedMetaFields = (data) => {
        dispatch('block-catfp/translate').allowedMetaFields(data);
    }

    // Update ACF fields allowed meta fields
    const AcfFields = () =>{
        const postMetaSync = catfp_global_object.postMetaSync === 'true';

        if(window.acf && !postMetaSync){
            const allowedTypes = ['text', 'textarea', 'wysiwyg'];
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

               if(field.data && field.data.key && allowedTypes.includes(field.data.type)){
                   const fieldName = field.data.name;
                   const inputType = field.data.type;

                   updateAllowedMetaFields({id: fieldName, type: inputType});
               }
            });
        }
    }

    // Update allowed meta fields
    Object.keys(AllowedMetaFields).forEach(key => {
        updateAllowedMetaFields({id: key, type: AllowedMetaFields[key].type});
    });

    // Update ACF fields allowed meta fields
    AcfFields();

    const ContentFetch = async () => {

        const contentFetchStatus = select('block-catfp/translate').contentFetchStatus();
        if (contentFetchStatus) {
            return;
        }

        /**
        * Prepare data to send in API request.
        */
        const apiSendData = {
            postId: parseInt(props.postId),
            local: props.targetLang,
            current_local: props.sourceLang,
            catfp_nonce: catfp_global_object.ajax_nonce,
            action: catfp_global_object.action_fetch
        };

        const contentController = new AbortController();
        apiController.push(contentController);

        /**
         * useEffect hook to fetch post data from the specified API endpoint.
         * Parses the response data and updates the state accordingly.
         * Handles errors in fetching post content.
         */
        await fetch(apiUrl, {
            method: 'POST',
            headers: {
                'content-type': 'application/x-www-form-urlencoded; charset=UTF-8',
                'Accept': 'application/json',
            },
            body: new URLSearchParams(apiSendData),
            signal: contentController.signal,
        })
            .then(response => response.json())
            .then(data => {

                const contentFetchStatus = select('block-catfp/translate').contentFetchStatus();
                
                if (contentFetchStatus) {
                    return;
                }

                const post_data = data.data;
                ClassicSaveSource(post_data);
                props.refPostData(post_data);
                props.updatePostDataFetch(true);
                dispatch('block-catfp/translate').contentFetchStatus(true);
            })
            .catch(error => {
                console.error('Error fetching post content:', error);
            });
    }

    await ContentFetch();
};

export default ClassicPostFetch;
