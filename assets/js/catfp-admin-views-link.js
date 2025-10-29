jQuery(document).ready(function(){
    const catfpSubsubsubList = jQuery('.catfp_subsubsub');

    if(catfpSubsubsubList.length){
        const $defaultSubsubsub = jQuery('ul.subsubsub:not(.catfp_subsubsub_list)');

        if($defaultSubsubsub.length){
            $defaultSubsubsub.after(catfpSubsubsubList);
            catfpSubsubsubList.show();
        }
    }
});
