import { useState, useEffect, useRef } from 'react';
import { MapPin, Loader2 } from 'lucide-react';
import { usePage } from '@inertiajs/react';

/**
 * AddressAutocomplete component using Mapbox Geocoding API
 * @param {Object} props
 * @param {string} props.value - Current address value
 * @param {Function} props.onChange - Callback when address changes
 * @param {Function} props.onSelect - Callback when address is selected (receives {address, lat, lon})
 * @param {string} props.placeholder - Placeholder text
 * @param {string} props.id - Input id
 * @param {string} props.name - Input name
 * @param {boolean} props.includeCoordinates - Whether to include lat/lon in onSelect callback
 * @param {string} props.className - Additional CSS classes
 * @param {boolean} props.textarea - Use textarea instead of input
 * @param {number} props.rows - Number of rows for textarea
 */
export default function AddressAutocomplete({
    value = '',
    onChange,
    onSelect,
    placeholder = 'Start typing an address...',
    id,
    name,
    includeCoordinates = true,
    className = '',
    textarea = false,
    rows = 3,
}) {
    const { mapbox } = usePage().props;
    const mapboxToken = mapbox?.access_token;
    const [suggestions, setSuggestions] = useState([]);
    const [isLoading, setIsLoading] = useState(false);
    const [showSuggestions, setShowSuggestions] = useState(false);
    const [selectedIndex, setSelectedIndex] = useState(-1);
    const [hoveredIndex, setHoveredIndex] = useState(-1);
    const [mapPreviewUrl, setMapPreviewUrl] = useState(null);
    const [isUserTyping, setIsUserTyping] = useState(false);
    const inputRef = useRef(null);
    const suggestionsRef = useRef(null);
    const debounceTimeoutRef = useRef(null);
    const isInitialMount = useRef(true);

    // Get the currently previewed suggestion (hovered or selected index, or first)
    const previewedSuggestion = suggestions.length > 0 
        ? suggestions[hoveredIndex >= 0 ? hoveredIndex : (selectedIndex >= 0 ? selectedIndex : 0)]
        : null;

    // Update map preview when previewed suggestion changes
    useEffect(() => {
        if (previewedSuggestion && mapboxToken && previewedSuggestion.center) {
            const [lon, lat] = previewedSuggestion.center;
            // Use Mapbox Static Images API to show a mini map
            const mapUrl = `https://api.mapbox.com/styles/v1/mapbox/streets-v12/static/pin-s+ff0000(${lon},${lat})/${lon},${lat},15,0/200x150?access_token=${mapboxToken}`;
            setMapPreviewUrl(mapUrl);
        } else {
            setMapPreviewUrl(null);
        }
    }, [previewedSuggestion, mapboxToken]);

    // Fetch address suggestions from Mapbox Geocoding API
    const fetchSuggestions = async (query) => {
        if (!query || query.length < 3) {
            setSuggestions([]);
            setIsLoading(false);
            return;
        }

        if (!mapboxToken) {
            console.error('Mapbox access token is not configured');
            setIsLoading(false);
            return;
        }

        setIsLoading(true);
        try {
            // Mapbox Geocoding API - forward geocoding for address autocomplete
            // Limit to South Africa (country code: za)
            const url = new URL('https://api.mapbox.com/geocoding/v5/mapbox.places/' + encodeURIComponent(query) + '.json');
            url.searchParams.append('access_token', mapboxToken);
            url.searchParams.append('country', 'za'); // Limit to South Africa
            url.searchParams.append('limit', '5');
            url.searchParams.append('types', 'address,poi,place'); // Focus on addresses and places

            const response = await fetch(url.toString());

            if (!response.ok) {
                throw new Error('Failed to fetch suggestions');
            }

            const data = await response.json();
            setSuggestions(data.features || []);
            setShowSuggestions(true);
            setSelectedIndex(-1);
        } catch (error) {
            console.error('Error fetching address suggestions:', error);
            setSuggestions([]);
        } finally {
            setIsLoading(false);
        }
    };

    // Debounced search - only fetch if user is actively typing
    useEffect(() => {
        // Skip fetching on initial mount if value exists (edit mode)
        if (isInitialMount.current && value) {
            isInitialMount.current = false;
            return;
        }
        isInitialMount.current = false;

        if (!isUserTyping) {
            return;
        }

        if (debounceTimeoutRef.current) {
            clearTimeout(debounceTimeoutRef.current);
        }

        debounceTimeoutRef.current = setTimeout(() => {
            fetchSuggestions(value);
        }, 300); // 300ms debounce

        return () => {
            if (debounceTimeoutRef.current) {
                clearTimeout(debounceTimeoutRef.current);
            }
        };
    }, [value, isUserTyping]);

    // Handle input change
    const handleInputChange = (e) => {
        const newValue = e.target.value;
        setIsUserTyping(true);
        onChange(newValue);
        // Only show suggestions if user is actively typing (length > 0)
        if (newValue.length > 0) {
            setShowSuggestions(true);
        } else {
            setShowSuggestions(false);
        }
    };

    // Handle suggestion selection
    const handleSelectSuggestion = (suggestion, event) => {
        // Prevent event bubbling to avoid conflicts with click-outside handler
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }
        
        // Mapbox response format: suggestion.place_name for full address
        // suggestion.center is [longitude, latitude]
        const address = suggestion.place_name || suggestion.text || '';
        const [lon, lat] = suggestion.center || [null, null];
        
        setIsUserTyping(false);
        onChange(address);
        setShowSuggestions(false);
        setSuggestions([]);
        setSelectedIndex(-1);
        setHoveredIndex(-1);

        // Blur the input to remove focus
        if (inputRef.current) {
            inputRef.current.blur();
        }

        if (onSelect) {
            onSelect({
                address,
                lat: lat ? parseFloat(lat) : null,
                lon: lon ? parseFloat(lon) : null,
            });
        }
    };

    // Handle keyboard navigation
    const handleKeyDown = (e) => {
        if (!showSuggestions || suggestions.length === 0) {
            return;
        }

        switch (e.key) {
            case 'ArrowDown':
                e.preventDefault();
                setSelectedIndex((prev) =>
                    prev < suggestions.length - 1 ? prev + 1 : prev
                );
                break;
            case 'ArrowUp':
                e.preventDefault();
                setSelectedIndex((prev) => (prev > 0 ? prev - 1 : -1));
                break;
            case 'Enter':
                e.preventDefault();
                if (selectedIndex >= 0 && selectedIndex < suggestions.length) {
                    handleSelectSuggestion(suggestions[selectedIndex], e);
                } else if (suggestions.length > 0) {
                    // If no selection but suggestions exist, select the first one
                    handleSelectSuggestion(suggestions[0], e);
                }
                break;
            case 'Escape':
                setShowSuggestions(false);
                setSelectedIndex(-1);
                break;
        }
    };

    // Close suggestions when clicking outside
    useEffect(() => {
        const handleClickOutside = (event) => {
            if (
                suggestionsRef.current &&
                !suggestionsRef.current.contains(event.target) &&
                inputRef.current &&
                !inputRef.current.contains(event.target)
            ) {
                setShowSuggestions(false);
                setIsUserTyping(false);
            }
        };

        // Use a slight delay to allow mousedown events on suggestions to fire first
        const timeoutId = setTimeout(() => {
            document.addEventListener('mousedown', handleClickOutside);
        }, 0);

        return () => {
            clearTimeout(timeoutId);
            document.removeEventListener('mousedown', handleClickOutside);
        };
    }, []);

    const InputComponent = textarea ? 'textarea' : 'input';
    const inputProps = textarea
        ? { rows }
        : { type: 'text' };

    return (
        <div className="relative">
            <div className="relative">
                <InputComponent
                    ref={inputRef}
                    id={id}
                    name={name}
                    value={value}
                    onChange={handleInputChange}
                    onKeyDown={handleKeyDown}
                    onFocus={() => {
                        // Only show suggestions if user has typed something or if they're actively searching
                        if (isUserTyping && value.length >= 3 && suggestions.length > 0) {
                            setShowSuggestions(true);
                        }
                    }}
                    placeholder={placeholder}
                    className={`${className} ${textarea ? '' : 'pr-10'}`}
                    {...inputProps}
                />
                {!textarea && (
                    <div className="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                        {isLoading ? (
                            <Loader2 className="h-5 w-5 text-gray-400 animate-spin" />
                        ) : (
                            <MapPin className="h-5 w-5 text-gray-400" />
                        )}
                    </div>
                )}
            </div>

            {showSuggestions && suggestions.length > 0 && (
                <div
                    ref={suggestionsRef}
                    className="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg"
                >
                    <div className="flex">
                        {/* Suggestions List */}
                        <div className="flex-1 max-h-60 overflow-auto">
                            {suggestions.map((suggestion, index) => {
                                // Mapbox format: suggestion.text is the primary text, suggestion.place_name is full address
                                const primaryText = suggestion.text || suggestion.place_name?.split(',')[0] || '';
                                const fullAddress = suggestion.place_name || suggestion.text || '';
                                
                                return (
                                    <button
                                        key={suggestion.id}
                                        type="button"
                                        onMouseDown={(e) => {
                                            // Use onMouseDown instead of onClick to prevent click-outside handler from firing first
                                            e.preventDefault();
                                            handleSelectSuggestion(suggestion, e);
                                        }}
                                        onMouseEnter={() => setHoveredIndex(index)}
                                        onMouseLeave={() => setHoveredIndex(-1)}
                                        className={`w-full text-left px-4 py-3 hover:bg-gray-100 focus:bg-gray-100 focus:outline-none transition-colors ${
                                            index === selectedIndex ? 'bg-gray-100' : ''
                                        } ${
                                            index < suggestions.length - 1
                                                ? 'border-b border-gray-200'
                                                : ''
                                        }`}
                                    >
                                        <div className="flex items-start">
                                            <MapPin className="h-4 w-4 text-gray-400 mt-0.5 mr-2 flex-shrink-0" />
                                            <div className="flex-1 min-w-0">
                                                <div className="text-sm font-medium text-gray-900 truncate">
                                                    {primaryText}
                                                </div>
                                                <div className="text-xs text-gray-500 truncate">
                                                    {fullAddress}
                                                </div>
                                            </div>
                                        </div>
                                    </button>
                                );
                            })}
                        </div>

                        {/* Mini Map Preview */}
                        {mapPreviewUrl && previewedSuggestion && (
                            <div className="hidden sm:block w-48 h-48 border-l border-gray-200 flex-shrink-0">
                                <img
                                    src={mapPreviewUrl}
                                    alt="Location preview"
                                    className="w-full h-full object-cover"
                                    onError={(e) => {
                                        // Hide map if image fails to load
                                        e.target.style.display = 'none';
                                    }}
                                />
                            </div>
                        )}
                    </div>
                </div>
            )}
        </div>
    );
}

