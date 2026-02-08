import { useState, useRef, useEffect } from 'react';
import { createPortal } from 'react-dom';
import { ChevronDown, X, Search } from 'lucide-react';

/**
 * SearchableDropdown - A searchable dropdown component
 * @param {Object} props
 * @param {Array} props.options - Array of options {id, name, ...}
 * @param {string|number} props.value - Selected value (id)
 * @param {Function} props.onChange - Callback when selection changes (receives id)
 * @param {string} props.placeholder - Placeholder text
 * @param {string} props.label - Label text
 * @param {string} props.id - Input id
 * @param {string} props.name - Input name
 * @param {boolean} props.required - Whether field is required
 * @param {string} props.className - Additional CSS classes
 * @param {string} props.displayKey - Key to display from option object (default: 'name')
 * @param {Function} props.getOptionLabel - Custom function to get display label (optional)
 * @param {boolean} props.disabled - Whether dropdown is disabled
 */
export default function SearchableDropdown({
    options = [],
    value = '',
    onChange,
    placeholder = 'Select an option...',
    label,
    id,
    name,
    required = false,
    className = '',
    displayKey = 'name',
    getOptionLabel,
    disabled = false,
    error,
}) {
    const [isOpen, setIsOpen] = useState(false);
    const [searchQuery, setSearchQuery] = useState('');
    const [filteredOptions, setFilteredOptions] = useState(options);
    const [position, setPosition] = useState({ top: 0, left: 0, width: 0 });
    const dropdownRef = useRef(null);
    const inputRef = useRef(null);
    const menuRef = useRef(null);

    // Filter options based on search query
    useEffect(() => {
        if (!searchQuery.trim()) {
            setFilteredOptions(options);
        } else {
            const query = searchQuery.toLowerCase();
            const filtered = options.filter(option => {
                const label = getOptionLabel 
                    ? getOptionLabel(option)
                    : (option[displayKey] || option.name || String(option));
                return label.toLowerCase().includes(query);
            });
            setFilteredOptions(filtered);
        }
    }, [searchQuery, options, displayKey, getOptionLabel]);

    // Calculate dropdown position when it opens
    useEffect(() => {
        if (isOpen && dropdownRef.current) {
            const updatePosition = () => {
                const rect = dropdownRef.current.getBoundingClientRect();
                setPosition({
                    top: rect.bottom + 4,
                    left: rect.left,
                    width: rect.width,
                });
            };

            updatePosition();
            
            // Update position on scroll (capture phase to catch all scroll events)
            const handleScroll = () => updatePosition();
            const handleResize = () => updatePosition();
            
            // Use capture phase to catch scroll events in nested containers
            document.addEventListener('scroll', handleScroll, true);
            window.addEventListener('resize', handleResize);
            window.addEventListener('scroll', handleScroll, true);

            return () => {
                document.removeEventListener('scroll', handleScroll, true);
                window.removeEventListener('resize', handleResize);
                window.removeEventListener('scroll', handleScroll, true);
            };
        }
    }, [isOpen]);

    // Close dropdown when clicking outside
    useEffect(() => {
        const handleClickOutside = (event) => {
            if (
                dropdownRef.current && 
                !dropdownRef.current.contains(event.target) &&
                menuRef.current &&
                !menuRef.current.contains(event.target)
            ) {
                setIsOpen(false);
                setSearchQuery('');
            }
        };

        if (isOpen) {
            document.addEventListener('mousedown', handleClickOutside);
            return () => {
                document.removeEventListener('mousedown', handleClickOutside);
            };
        }
    }, [isOpen]);

    // Focus input when dropdown opens
    useEffect(() => {
        if (isOpen && inputRef.current) {
            inputRef.current.focus();
        }
    }, [isOpen]);

    const selectedOption = options.find(opt => String(opt.id || opt) === String(value));

    const getDisplayValue = () => {
        if (!selectedOption) return '';
        return getOptionLabel 
            ? getOptionLabel(selectedOption)
            : (selectedOption[displayKey] || selectedOption.name || String(selectedOption));
    };

    const handleSelect = (option) => {
        const optionValue = option.id || option;
        onChange(optionValue);
        setIsOpen(false);
        setSearchQuery('');
    };

    const handleClear = (e) => {
        e.stopPropagation();
        onChange('');
        setSearchQuery('');
    };

    return (
        <div className={`${className} relative`}>
            {label && (
                <label htmlFor={id} className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
                    {label} {required && <span className="text-red-500">*</span>}
                </label>
            )}
            <div className="relative z-10" ref={dropdownRef}>
                <div
                    className={`relative w-full cursor-pointer rounded-md border ${
                        error 
                            ? 'border-red-300 focus-within:border-red-500 focus-within:ring-red-500' 
                            : 'border-gray-300 focus-within:border-primary-500 focus-within:ring-primary-500'
                    } bg-white dark:bg-gray-700 dark:border-gray-600 shadow-sm focus-within:ring-1 focus-within:ring-inset sm:text-sm ${
                        disabled ? 'opacity-50 cursor-not-allowed' : ''
                    }`}
                    onClick={() => !disabled && setIsOpen(!isOpen)}
                >
                    {!isOpen && (
                        <div className="flex items-center justify-between px-3 py-2">
                            <span className={`block truncate ${selectedOption ? 'text-gray-900 dark:text-gray-100' : 'text-gray-500 dark:text-gray-400'}`}>
                                {getDisplayValue() || placeholder}
                            </span>
                            <div className="flex items-center space-x-2">
                                {selectedOption && !disabled && (
                                    <X
                                        className="h-4 w-4 text-gray-400 hover:text-gray-600"
                                        onClick={handleClear}
                                    />
                                )}
                                <ChevronDown className="h-4 w-4 text-gray-400" />
                            </div>
                        </div>
                    )}
                    {isOpen && (
                        <div className="p-2">
                            <div className="relative">
                                <Search className="absolute left-2 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                                <input
                                    ref={inputRef}
                                    type="text"
                                    value={searchQuery}
                                    onChange={(e) => setSearchQuery(e.target.value)}
                                    className="block w-full pl-8 pr-3 py-1 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500 sm:text-sm dark:bg-gray-800 dark:border-gray-600 dark:text-gray-100"
                                    placeholder="Search..."
                                    onClick={(e) => e.stopPropagation()}
                                />
                            </div>
                        </div>
                    )}
                </div>
            </div>
            {error && (
                <p className="mt-1 text-sm text-red-600">{error}</p>
            )}
            <input
                type="hidden"
                id={id}
                name={name}
                value={value}
                required={required}
            />
            {isOpen && typeof document !== 'undefined' && createPortal(
                <div
                    ref={menuRef}
                    className="fixed z-[9999] bg-white dark:bg-gray-700 shadow-lg max-h-60 rounded-md py-1 text-sm ring-1 ring-black ring-opacity-5 overflow-auto focus:outline-none"
                    style={{
                        top: `${position.top}px`,
                        left: `${position.left}px`,
                        width: `${Math.max(position.width * 1.5, 400)}px`,
                        maxWidth: '90vw',
                    }}
                >
                    {filteredOptions.length > 0 ? (
                        filteredOptions.map((option) => {
                            const optionValue = option.id || option;
                            const isSelected = String(optionValue) === String(value);
                            const label = getOptionLabel 
                                ? getOptionLabel(option)
                                : (option[displayKey] || option.name || String(option));

                            return (
                                <div
                                    key={optionValue}
                                    className={`cursor-pointer select-none relative py-1.5 pl-3 pr-9 ${
                                        isSelected
                                            ? 'bg-primary-600 text-white'
                                            : 'text-gray-900 dark:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-600'
                                    }`}
                                    onClick={() => handleSelect(option)}
                                >
                                    <span className="block break-words">{label}</span>
                                    {isSelected && (
                                        <span className="absolute inset-y-0 right-0 flex items-center pr-4">
                                            <svg className="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
                                            </svg>
                                        </span>
                                    )}
                                </div>
                            );
                        })
                    ) : (
                        <div className="cursor-default select-none relative py-2 px-4 text-gray-700 dark:text-gray-300">
                            No options found
                        </div>
                    )}
                </div>,
                document.body
            )}
        </div>
    );
}

