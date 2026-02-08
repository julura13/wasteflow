import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import Modal from '@/Components/Modal';
import { useForm, router } from '@inertiajs/react';
import { Upload, X, User, AlertTriangle } from 'lucide-react';
import { useState, useRef, useEffect } from 'react';

export default function AvatarUploadForm({ user, className = '', status }) {
    const [preview, setPreview] = useState(null);
    const [imageError, setImageError] = useState(false);
    const [showDeleteModal, setShowDeleteModal] = useState(false);
    const fileInputRef = useRef(null);
    
    const { data, setData, post, errors, processing, recentlySuccessful, reset } = useForm({
        avatar: null,
    });

    useEffect(() => {
        setImageError(false);
        
        if (user?.avatar && !preview && !data.avatar) {
        } else if (!user?.avatar && !preview && !data.avatar) {
            if (fileInputRef.current) {
                fileInputRef.current.value = '';
            }
        }
    }, [user?.avatar, preview, data.avatar]);

    const handleFileChange = (e) => {
        const file = e.target.files[0];
        if (file) {
            setData('avatar', file);
            const reader = new FileReader();
            reader.onloadend = () => {
                setPreview(reader.result);
            };
            reader.readAsDataURL(file);
        } else {
            setPreview(null);
            setData('avatar', null);
        }
    };

    const submit = (e) => {
        e.preventDefault();
        if (!data.avatar) return;
        
        post(route('profile.avatar.upload'), {
            forceFormData: true,
            onSuccess: () => {
                setPreview(null);
                setData('avatar', null);
                reset();
                if (fileInputRef.current) {
                    fileInputRef.current.value = '';
                }
                router.reload({ only: ['user'] });
            },
            onError: () => {
            },
        });
    };

    const handleCancel = () => {
        setPreview(null);
        setData('avatar', null);
        reset();
        if (fileInputRef.current) {
            fileInputRef.current.value = '';
        }
    };

    const handleDelete = () => {
        setShowDeleteModal(true);
    };

    const handleDeleteConfirm = () => {
        router.delete(route('profile.avatar.delete'), {
            onSuccess: () => {
                setPreview(null);
                setData('avatar', null);
                reset();
                if (fileInputRef.current) {
                    fileInputRef.current.value = '';
                }
                setShowDeleteModal(false);
                router.reload({ only: ['user'] });
            },
        });
    };

    return (
        <section className={className}>
            <header>
                <h2 className="text-lg font-medium text-gray-900 dark:text-gray-100">
                    Profile Avatar
                </h2>
                <p className="mt-1 text-sm text-gray-600 dark:text-gray-300">
                    Update your profile picture.
                </p>
            </header>

            <div className="mt-6">
                <div className="flex items-center space-x-6">
                    <div className="flex-shrink-0">
                        {preview ? (
                            <img
                                src={preview}
                                alt="Preview"
                                className="h-24 w-24 rounded-full object-cover"
                            />
                        ) : user?.avatar && !imageError ? (
                            <img
                                src={user.avatar}
                                alt={user.name}
                                className="h-24 w-24 rounded-full object-cover"
                                onError={() => setImageError(true)}
                            />
                        ) : (
                            <div className="h-24 w-24 rounded-full bg-primary-600 text-white flex items-center justify-center text-2xl font-medium">
                                {user?.name?.charAt(0).toUpperCase() || <User className="h-12 w-12" />}
                            </div>
                        )}
                    </div>
                    <div className="flex-1">
                        <form onSubmit={submit} className="space-y-4">
                            <div>
                                <InputLabel htmlFor="avatar" value="Upload Avatar" />
                                <input
                                    ref={fileInputRef}
                                    id="avatar"
                                    type="file"
                                    accept="image/*"
                                    onChange={handleFileChange}
                                    className="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100"
                                />
                                <InputError className="mt-2" message={errors.avatar} />
                                <p className="mt-1 text-xs text-gray-500">
                                    PNG, JPG, GIF up to 2MB
                                </p>
                            </div>
                            {preview && (
                                <div className="flex items-center space-x-3">
                                    <PrimaryButton type="submit" disabled={processing}>
                                        <Upload className="h-4 w-4 mr-2" />
                                        {processing ? 'Uploading...' : 'Upload Avatar'}
                                    </PrimaryButton>
                                    <button
                                        type="button"
                                        onClick={handleCancel}
                                        disabled={processing}
                                        className="text-sm text-gray-600 hover:text-gray-900 disabled:opacity-50"
                                    >
                                        Cancel
                                    </button>
                                </div>
                            )}
                        </form>
                        {user?.has_avatar && !preview && (
                            <button
                                onClick={handleDelete}
                                className="mt-2 text-sm text-red-600 hover:text-red-900"
                            >
                                <X className="h-4 w-4 inline mr-1" />
                                Remove Avatar
                            </button>
                        )}
                        {(recentlySuccessful || status === 'avatar-uploaded' || status === 'avatar-deleted') && (
                            <p className="mt-2 text-sm text-green-600">
                                {status === 'avatar-deleted' ? 'Avatar removed successfully!' : 'Avatar updated successfully!'}
                            </p>
                        )}
                    </div>
                </div>
            </div>

            <Modal show={showDeleteModal} onClose={() => setShowDeleteModal(false)} maxWidth="md">
                <div className="p-6">
                    <div className="flex items-center mb-4">
                        <AlertTriangle className="h-6 w-6 text-red-600 mr-3" />
                        <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100">
                            Remove Avatar
                        </h3>
                    </div>
                    <p className="text-sm text-gray-600 dark:text-gray-400 mb-6">
                        Are you sure you want to remove your avatar? This action cannot be undone.
                    </p>
                    <div className="flex justify-end space-x-3">
                        <button
                            onClick={() => setShowDeleteModal(false)}
                            className="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-600"
                        >
                            Cancel
                        </button>
                        <button
                            onClick={handleDeleteConfirm}
                            className="px-4 py-2 text-sm font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                        >
                            Remove Avatar
                        </button>
                    </div>
                </div>
            </Modal>
        </section>
    );
}

