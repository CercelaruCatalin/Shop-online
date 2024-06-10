<script lang="ts">
    import { createOrder } from '../../../api/orders';
    import { writable, get } from 'svelte/store';
    import type Orders from '../../../interfaces/orders';
    import { onMount } from 'svelte';

    import  orderData  from '../../orders/create/+page.svelte'; // Import orderData store
    import { page } from '$app/stores';

    const formData = writable<Orders>({
        id: 0,
        name: '',
        phone: '',
        email: '',
        method: '',
        address: '',
        placed_on: '',
        payment_status: '',
        status: '',
        total_price: 0,
        items: [],
    });

    const successMessage = writable<string>('');
    const errorMessage = writable<string>('');

    onMount(() => {
        console.log("Order Data:", $orderData);
        if ($orderData) {
            formData.set($orderData);
        }
    });

    const handleSubmit = async () => {
        try {
            const result = await createOrder(get(formData));
            if (result) {
                successMessage.set('Order created successfully!');
                formData.update(() => ({
                    id: 0,
                    name: '',
                    phone: '',
                    email: '',
                    method: '',
                    address: '',
                    placed_on: '',
                    payment_status: '',
                    status: '',
                    total_price: 0,
                    items: [],
                }));
            } else {
                errorMessage.set('Failed to create order. Please try again.');
            }
        } catch (error) {
            console.error('Error creating order:', error);
            errorMessage.set('Failed to create order. Please try again.');
        }
    }
</script>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-6 text-center">Create New Order</h1>

    {$successMessage}
    {$errorMessage}

    <form on:submit|preventDefault={handleSubmit}>
        <div class="grid grid-cols-2 gap-4">
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="name">Name</label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leadingtight focus:outline-none focus:shadow-outline" type="text" bind:value={$formData.name} id="name">
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="phone">Phone</label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" type="text" bind:value={$formData.phone} id="phone">
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="email">Email</label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" type="text" bind:value={$formData.email} id="email">
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="method">Method</label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" type="text" bind:value={$formData.method} id="method">
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="address">Address</label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" type="text" bind:value={$formData.address} id="address">
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="placed_on">Placed On</label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" type="text" bind:value={$formData.placed_on} id="placed_on">
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="payment_status">Payment Status</label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" type="text" bind:value={$formData.payment_status} id="payment_status">
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="status">Status</label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" type="text" bind:value={$formData.status} id="status">
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="total_price">Total Price</label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" type="text" value={$formData.total_price} readonly>
            </div>
            <!-- Add input fields for other properties as needed -->
        </div>

        <div class="flex justify-center mt-6">
            <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded" type="submit">Create Order</button>
        </div>
    </form>
</div>
