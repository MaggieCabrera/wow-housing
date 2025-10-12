// Grid overlay functionality - can be easily removed later
function createGridOverlay() {
    const mapBackground = document.getElementById('mapBackground');
    
    // Remove existing grid if it exists
    const existingGrid = document.getElementById('gridOverlay');
    if (existingGrid) {
        existingGrid.remove();
    }
    
    // Create grid container
    const gridOverlay = document.createElement('div');
    gridOverlay.id = 'gridOverlay';
    gridOverlay.className = 'grid-overlay';
    
    // Get the actual map image dimensions and position
    const mapImage = new Image();
    mapImage.onload = function() {
        const mapRect = mapBackground.getBoundingClientRect();
        const containerWidth = mapRect.width;
        const containerHeight = mapRect.height;
        
        // Calculate the actual displayed image dimensions (maintaining aspect ratio)
        const imageAspectRatio = mapImage.width / mapImage.height;
        const containerAspectRatio = containerWidth / containerHeight;
        
        let imageWidth, imageHeight, offsetX, offsetY;
        
        if (imageAspectRatio > containerAspectRatio) {
            // Image is wider than container
            imageWidth = containerWidth;
            imageHeight = containerWidth / imageAspectRatio;
            offsetX = 0;
            offsetY = (containerHeight - imageHeight) / 2;
        } else {
            // Image is taller than container
            imageHeight = containerHeight;
            imageWidth = containerHeight * imageAspectRatio;
            offsetX = (containerWidth - imageWidth) / 2;
            offsetY = 0;
        }
        
        // Calculate scale factor from original image to displayed image
        const scaleX = imageWidth / mapImage.width;
        const scaleY = imageHeight / mapImage.height;
        
        // Create grid based on original image coordinates (scaled to displayed size)
        const gridSpacing = 100; // 100px in original image
        const scaledSpacing = gridSpacing * scaleX;
        
        // Create vertical lines
        for (let x = 0; x <= mapImage.width; x += gridSpacing) {
            const scaledX = offsetX + (x * scaleX);
            const line = document.createElement('div');
            line.className = 'grid-line vertical';
            line.style.left = scaledX + 'px';
            line.style.top = offsetY + 'px';
            line.style.height = imageHeight + 'px';
            gridOverlay.appendChild(line);
            
            // Add X-axis label
            if (x > 0) {
                const label = document.createElement('div');
                label.className = 'grid-label x-label';
                label.textContent = x;
                label.style.left = scaledX + 'px';
                label.style.top = (offsetY + 10) + 'px';
                gridOverlay.appendChild(label);
            }
        }
        
        // Create horizontal lines
        for (let y = 0; y <= mapImage.height; y += gridSpacing) {
            const scaledY = offsetY + (y * scaleY);
            const line = document.createElement('div');
            line.className = 'grid-line horizontal';
            line.style.top = scaledY + 'px';
            line.style.left = offsetX + 'px';
            line.style.width = imageWidth + 'px';
            gridOverlay.appendChild(line);
            
            // Add Y-axis label
            if (y > 0) {
                const label = document.createElement('div');
                label.className = 'grid-label y-label';
                label.textContent = y;
                label.style.top = scaledY + 'px';
                label.style.left = (offsetX - 30) + 'px';
                gridOverlay.appendChild(label);
            }
        }
        
        // Add grid to map
        mapBackground.appendChild(gridOverlay);
    };
    
        // Respect selected map filename if provided by the page
        const pageMapBackground = document.getElementById('mapBackground');
        const selected = (window.selectedMapFilename || (pageMapBackground && pageMapBackground.dataset && pageMapBackground.dataset.map) || 'alliance_map.jpg');
        const selectedPath = (selected.startsWith('http') || selected.startsWith('/') || selected.startsWith('maps/')) ? selected : ('maps/' + selected);
        mapImage.src = selectedPath;
    }

    function removeGridOverlay() {
        const gridOverlay = document.getElementById('gridOverlay');
        if (gridOverlay) {
            gridOverlay.remove();
        }
    }

// Toggle grid with keyboard shortcut (G key)
document.addEventListener('keydown', function(event) {
    if (event.key === 'g' || event.key === 'G') {
        const gridOverlay = document.getElementById('gridOverlay');
        if (gridOverlay) {
            removeGridOverlay();
            console.log('Grid overlay removed');
        } else {
            createGridOverlay();
            console.log('Grid overlay created');
        }
    }
});

// Recreate grid when window is resized
let resizeTimeout;
window.addEventListener('resize', function() {
    const gridOverlay = document.getElementById('gridOverlay');
    if (gridOverlay) {
        // Clear existing timeout to prevent multiple rapid calls
        clearTimeout(resizeTimeout);
        // Update immediately for responsiveness
        createGridOverlay();
    }
});
