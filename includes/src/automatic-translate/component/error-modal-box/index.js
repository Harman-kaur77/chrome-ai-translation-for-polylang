import CopyClipboard from "../copy-clipboard";
import { useEffect } from "@wordpress/element";
import DOMPurify from 'dompurify';

const ErrorModalBox = ({ message, onClose, Title }) => {

    let dummyElement = jQuery('<div>').append(message);
    const stringifiedMessage = dummyElement.html();
    dummyElement.remove();
    dummyElement = null;

    useEffect(() => {
        const clipboardElements = document.querySelectorAll('.chrome-ai-translator-flags');

        if (clipboardElements.length > 0) {
            clipboardElements.forEach(element => {

                element.classList.add('catfp-tooltip-element');

                element.addEventListener('click', (e) => {
                    e.preventDefault();
                    const toolTipExists = element.querySelector('.catfp-tooltip');
                    
                    if(toolTipExists){
                        return;
                    }

                    let toolTipElement = document.createElement('span');
                    toolTipElement.textContent = "Text to be copied.";
                    toolTipElement.className = 'catfp-tooltip';
                    element.appendChild(toolTipElement);

                    CopyClipboard({ text: element.getAttribute('data-clipboard-text'), startCopyStatus: () => {
                        toolTipElement.classList.add('catfp-tooltip-active');
                    }, endCopyStatus: () => {
                        setTimeout(() => {
                            toolTipElement.remove();
                        }, 800);
                    } });
                });
            });

            return () => {
                clipboardElements.forEach(element => {
                    element.removeEventListener('click', () => { });
                });
            };
        }
    }, []);

    return (
        <div className="catfp-error-modal-box-container">
            <div className="catfp-error-modal-box">
                <div className="catfp-error-modal-box-header">
                    <span className="catfp-error-modal-box-close" onClick={onClose}>×</span>
                    {Title && <h3>{Title}</h3>}
                </div>
                <div className="catfp-error-modal-box-body">
                    <p dangerouslySetInnerHTML={{ __html: DOMPurify.sanitize(stringifiedMessage) }} />
                </div>
                <div className="catfp-error-modal-box-footer">
                    <button className="catfp-error-modal-box-close button button-primary" onClick={onClose}>Close</button>
                </div>
            </div>
        </div>
    );
};

export default ErrorModalBox;
