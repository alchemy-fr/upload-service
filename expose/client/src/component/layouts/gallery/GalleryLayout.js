import React from 'react';
import ImageGallery from 'react-image-gallery';
import {dataShape} from "../../props/dataShape";
import {PropTypes} from 'prop-types';
import Description from "../shared-components/Description";
import {defaultMapProps, initMapbox} from "../mapbox/MapboxLayout";
import mapboxgl from 'mapbox-gl';
import DownloadButton from "../shared-components/DownloadButton";
import DownloadViaEmailModal from "../shared-components/DownloadViaEmailModal";
import {
    downloadContainerDefaultState, onDownload,
    renderDownloadTermsModal,
    renderDownloadViaEmail
} from "../shared-components/DownloadViaEmailProxy";
import AssetProxy from "../shared-components/AssetProxy";

class GalleryLayout extends React.Component {
    static propTypes = {
        data: dataShape,
        assetId: PropTypes.string,
        options: PropTypes.object,
        mapOptions: PropTypes.object,
    };

    state = {
        showFullscreenButton: true,
        showPlayButton: true,
        currentIndex: null,
        ...downloadContainerDefaultState,
    };

    map;

    constructor(props) {
        super(props);

        this.mapContainer = React.createRef();
        this.sliderRef = React.createRef();
    }

    componentDidMount() {
        if (this.props.options.displayMap) {
            this.initMap();
        }
    }

    initMap() {
        if (!this.mapContainer.current) {
            return;
        }

        const {data, options, mapOptions} = this.props;

        let locationAsset = data.assets.filter(a => a.asset.lat)[0];
        locationAsset = locationAsset ? locationAsset.asset : mapOptions;

        switch (options.mapProvider) {
            default:
            case 'mapbox':
                this.map = initMapbox(this.mapContainer.current, {
                    ...defaultMapProps,
                    ...(locationAsset ? {
                        lat: locationAsset.lat,
                        lng: locationAsset.lng,
                    } : {}),
                    zoom: locationAsset && locationAsset.zoom ? locationAsset.zoom : 5
                });
                data.assets.forEach((a, pos) => {
                    const {asset} = a;
                    if (!(asset.lat && asset.lng)) {
                        return;
                    }
                    const marker = new mapboxgl.Marker()
                        .setLngLat([
                            asset.lng,
                            asset.lat,])
                        .addTo(this.map)
                    ;

                    marker.getElement().addEventListener('click', () => {
                        this.goto(pos);
                    });
                });
                break;
        }
    }

    goto(index) {
        if (!this.sliderRef.current) {
            return;
        }
        this.sliderRef.current.slideToIndex(index);
    }

    onSlide = (offset) => {
        const asset = this.props.data.assets[offset].asset;

        const displayControls = !(asset.mimeType.indexOf('video/') === 0);

        this.setState({
            currentIndex: offset,
            showFullscreenButton: displayControls,
            showPlayButton: displayControls,

        });

        if (this.map) {
            if (asset.lat && asset.lng) {
                this.map.flyTo({
                    center: [
                        asset.lng,
                        asset.lat,
                    ],
                    essential: true
                });
            }
        }
    };

    render() {
        const {assetId, data, options} = this.props;
        const {currentIndex} = this.state;
        const {
            title,
            assets,
        } = data;

        const {
            showFullscreenButton,
            showPlayButton,
        } = this.state;

        let startIndex = 0;
        if (currentIndex) {
            startIndex = currentIndex;
        } else if (assetId) {
            startIndex = assets.findIndex(a => a.id === assetId);
            if (startIndex < 0) {
                startIndex = assets.findIndex(a => a.slug === assetId);
                if (startIndex < 0) {
                    startIndex = 0;
                }
            }
        }

        return <div className={`layout-gallery`}>
            {renderDownloadTermsModal.call(this)}
            {renderDownloadViaEmail.call(this)}
            <h1>{title}</h1>
            <Description
                descriptionHtml={data.description}
            />
            {assets.length > 0 ?
                <ImageGallery
                    ref={this.sliderRef}
                    startIndex={startIndex}
                    onSlide={this.onSlide}
                    showFullscreenButton={showFullscreenButton}
                    showPlayButton={showPlayButton}
                    items={assets.map(a => ({
                        original: a.asset.url,
                        thumbnail: a.asset.thumbUrl,
                        description: 'toto',
                        asset: a.asset,
                        renderItem: this.renderItem,
                    }))}
                /> : 'Gallery is empty'}
            {options.displayMap ? this.renderMap() : ''}
        </div>
    }

    renderMap() {
        return <div className={'gallery-map'}>
            <div
                className={'map-container'}
                ref={this.mapContainer}
            />
        </div>
    }

    renderDownloadViaEmail() {
        return <DownloadViaEmailModal
            url={this.state.pendingDownloadUrl}
            onClose={this.discardDownloadViaEmail}
        />
    }

    discardDownloadViaEmail = () => {
        this.setState({
            displayDownloadViaEmail: false,
            pendingDownloadUrl: null,
        });
    }

    renderItem = ({asset}) => {
        return <div className="image-gallery-image layout-asset-container">
            {asset.downloadUrl ? <div
                className="download-btn">
                <DownloadButton
                    downloadUrl={asset.downloadUrl}
                    onDownload={onDownload.bind(this)}
                />
            </div> : ''}
            <AssetProxy asset={asset}/>
            {asset.description ? <div
                className="image-gallery-description">
                <Description descriptionHtml={asset.description}/>
            </div> : ''}
        </div>;
    }
}

export default GalleryLayout;
