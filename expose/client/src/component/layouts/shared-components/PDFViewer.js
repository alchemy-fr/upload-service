import React, {useState, useRef} from 'react';
import {usePdf} from '@mikecousins/react-pdf';

const PDFViewer = (props) => {
    const [page] = useState(1);
    const canvasRef = useRef(null);

    const {pdfDocument} = usePdf({
        file: props.file,
        page,
        scale: .8,
        canvasRef,
    });

    return <div className={'pdf-viewer'}>
        {!pdfDocument && <span>Loading...</span>}
        <canvas
            ref={canvasRef}
        />
    </div>
};

export default PDFViewer;
