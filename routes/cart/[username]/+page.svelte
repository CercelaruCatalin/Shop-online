<script lang="ts">
  import { onMount } from 'svelte';
  import { goto } from '$app/navigation';
  import { getUserCart, deleteItemFromCart, updateQuantityInCart } from '../../../api/carts';
  import type CartItem from '../../../interfaces/carts';
  import { writable, get } from 'svelte/store';
  export let orderData = writable<any>(null); // Export orderData store

  const cartItems = writable<CartItem[]>([]);
  const selectedQuantity = writable<number>(1);

  const fetchCartItems = async () => {
      try {
          const username = window.location.pathname.split('/')[2];
          if (!username) {
              throw new Error('Username parameter is missing');
          }
          const fetchedCartItems = await getUserCart(username);
          cartItems.set(fetchedCartItems);
      } catch (error: any) {
          console.error('Error fetching cart items:', error.message);
      }
  };

  onMount(fetchCartItems);

  const updateLocalQuantity = (productId: string, quantity: number) => {
      cartItems.update(items => {
          return items.map(item => {
              if (item.productId === productId) {
                  return { ...item, quantity };
              }
              return item;
          });
      });
  }

  const updateQuantity = async (productId: string, quantity: number) => {
      try {
          const username = window.location.pathname.split('/')[2];
          if (!username) {
              throw new Error('Username parameter is missing');
          }
          await updateQuantityInCart(username, productId, quantity);
          updateLocalQuantity(productId, quantity);
      } catch (error: any) {
          console.error('Error updating item quantity:', error.message);
      }
  }

  const removeFromCart = async (productId: string) => {
      try {
          const username = window.location.pathname.split('/')[2];
          if (!username) {
              throw new Error('Username parameter is missing');
          }
          await deleteItemFromCart(username, productId);
          cartItems.update(items => items.filter(item => item.productId !== productId));
      } catch (error: any) {
          console.error('Error removing item from cart:', error.message);
      }
  }

  const proceedToCheckout = () => {
      const items = get(cartItems);
      const totalPrice = items.reduce((acc, item) => acc + (item.pricePerItem * item.quantity), 0);
      const formattedTotalPrice = totalPrice.toLocaleString('en-US', { style: 'currency', currency: 'USD' });
      const orderDataValue = {
          items: items.map(item => ({
              productId: item.productId,
              quantity: item.quantity,
              price: item.pricePerItem
          })),
          totalPrice: formattedTotalPrice
      };
      orderData.set(orderDataValue); // Set the order data to the store
      goto('/orders/create');
  }
</script>

<div class="min-h-screen flex items-center justify-center bg-gray-100">
  <div class="container mx-auto px-4 py-8">
      <h1 class="text-3xl font-bold mb-6 text-center">Shopping Cart</h1>

      {#if $cartItems.length > 0}
          {#each $cartItems as item, index}
              <div class="bg-white rounded-lg overflow-hidden shadow-lg mb-6 border-2 border-black">
                  <div class="bg-gray-100 px-4 py-2 border-b">
                      <h2 class="text-lg font-semibold">Product ID: {item.productId}</h2>
                      <p class="text-sm text-gray-600">Name: {item.name}</p>
                  </div>
                  <div class="p-4">
                      <p class="mb-2"><span class="font-semibold">Quantity:</span> {item.quantity}</p>
                      <p class="mb-2"><span class="font-semibold">Price:</span> ${item.pricePerItem * item.quantity}</p>
                      <input type="number" bind:value={item.quantity} min="1" max="10" />
                      <button on:click={() => updateQuantity(item.productId, item.quantity)} class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">Update Cart</button>
                      <button on:click={() => removeFromCart(item.productId)} class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">Remove from Cart</button>
                  </div>
              </div>
              {#if index !== $cartItems.length - 1}
                  <div class="mb-6"></div>
              {/if}
          {/each}
          <button on:click={proceedToCheckout} class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Proceed to Checkout</button>
      {:else}
          <p class="text-gray-600 text-center">Your cart is empty.</p>
      {/if}
  </div>
</div>